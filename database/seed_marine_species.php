<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

function ensure_marine_species_seed_schema(PDO $pdo): void
{
    $columns = array_column($pdo->query('SHOW COLUMNS FROM marine_species')->fetchAll(), 'Field');
    $definitions = [
        'category' => 'VARCHAR(60) NULL AFTER scientific_name',
        'occurrence_note' => 'TEXT NULL AFTER diet',
        'image_source_url' => 'TEXT NULL AFTER species_image',
        'reference_url' => 'TEXT NULL AFTER image_source_url',
    ];

    foreach ($definitions as $name => $definition) {
        if (!in_array($name, $columns, true)) {
            $pdo->exec("ALTER TABLE marine_species ADD COLUMN {$name} {$definition}");
        }
    }

    $indexes = $pdo->query('SHOW INDEX FROM marine_species')->fetchAll();
    $hasCategoryIndex = false;
    $hasUniqueScientificIndex = false;
    $uniqueIndexNameInUse = false;
    foreach ($indexes as $index) {
        if ($index['Column_name'] === 'category') {
            $hasCategoryIndex = true;
        }
        if ($index['Column_name'] === 'scientific_name' && (int) $index['Non_unique'] === 0) {
            $hasUniqueScientificIndex = true;
        }
        if ($index['Key_name'] === 'uq_species_scientific_name') {
            $uniqueIndexNameInUse = true;
        }
    }

    if (!$hasUniqueScientificIndex) {
        if ($uniqueIndexNameInUse) {
            throw new RuntimeException('The uq_species_scientific_name index name is already used by another index.');
        }
        $duplicates = $pdo->query(
            "SELECT LOWER(TRIM(scientific_name)) AS name, COUNT(*) AS duplicates
             FROM marine_species
             WHERE scientific_name IS NOT NULL
             GROUP BY LOWER(TRIM(scientific_name))
             HAVING COUNT(*) > 1"
        )->fetchAll();
        if ($duplicates) {
            $names = array_map(static fn (array $row): string => $row['name'], $duplicates);
            throw new RuntimeException('Cannot add the scientific-name uniqueness constraint until existing duplicates are reviewed: ' . implode(', ', $names));
        }
        $pdo->exec('ALTER TABLE marine_species ADD UNIQUE KEY uq_species_scientific_name (scientific_name)');
    }

    if (!$hasCategoryIndex) {
        $pdo->exec('ALTER TABLE marine_species ADD KEY idx_species_category (category)');
    }
}

function validate_species_seed_record(array $record): void
{
    foreach (['common_name', 'scientific_name', 'category', 'description', 'habitat', 'diet', 'occurrence_note', 'conservation_status', 'interesting_facts'] as $requiredField) {
        if (!isset($record[$requiredField]) || trim((string) $record[$requiredField]) === '') {
            throw new RuntimeException('A seed record is missing required field: ' . $requiredField);
        }
    }

    if (!in_array($record['category'], marine_species_categories(), true)) {
        throw new RuntimeException('Unsupported species category for ' . $record['scientific_name'] . '.');
    }
    if (mb_strlen($record['common_name']) > 150 || mb_strlen($record['scientific_name']) > 180 || mb_strlen($record['category']) > 60 || mb_strlen($record['conservation_status']) > 100) {
        throw new RuntimeException('A species name, category, or conservation status exceeds its database column length.');
    }
    if (empty($record['references']) || !is_array($record['references'])) {
        throw new RuntimeException('At least one reference URL is required for ' . $record['scientific_name'] . '.');
    }
    foreach ($record['references'] as $referenceUrl) {
        $parts = parse_url((string) $referenceUrl);
        if (!filter_var($referenceUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            throw new RuntimeException('An invalid reference URL was provided for ' . $record['scientific_name'] . '.');
        }
    }
}

function marine_species_obis_url(string $scientificName, bool $regional = false): string
{
    $geometry = $regional
        ? 'POLYGON((121.0 12.5,122.5 12.5,122.5 13.9,121.0 13.9,121.0 12.5))'
        : 'POLYGON((121.6 13.1,122.2 13.1,122.2 13.7,121.6 13.7,121.6 13.1))';
    return 'https://api.obis.org/v3/occurrence?' . http_build_query([
        'geometry' => $geometry,
        'scientificname' => $scientificName,
        'size' => 1,
    ]);
}

function marine_species_fishbase_url(string $scientificName): string
{
    return 'https://www.fishbase.se/summary/' . str_replace(' ', '-', strtolower($scientificName)) . '.html';
}

$fishProfiles = [
    'reef_plankton' => [
        'description' => 'A small reef fish that feeds in the water around coral outcrops and shelters among reef structures.',
        'habitat' => 'Shallow tropical coral reefs, lagoons, and reef slopes.',
        'diet' => 'Small planktonic animals and, depending on age and species, algae.',
        'fact' => 'Many small reef fishes use coral branches and crevices as protection from predators.',
    ],
    'reef_algae' => [
        'description' => 'A reef-associated fish that grazes on plant material and helps move energy through coastal reef habitats.',
        'habitat' => 'Nearshore reefs, reef flats, lagoons, and areas with algae-covered rock.',
        'diet' => 'Algae and other plant material growing on hard surfaces.',
        'fact' => 'Grazing fishes help prevent algae from overgrowing reef surfaces.',
    ],
    'butterflyfish' => [
        'description' => 'A colorful butterflyfish that searches coral reefs for food among branching corals and reef crevices.',
        'habitat' => 'Clear tropical lagoons and coral-rich reef slopes.',
        'diet' => 'Coral polyps and small invertebrates; the balance varies by species.',
        'fact' => 'Butterflyfish have narrow snouts that help them feed around coral branches.',
    ],
    'cardinalfish' => [
        'description' => 'A small reef fish that spends much of the day close to shelter and becomes more active around dusk or at night.',
        'habitat' => 'Reef crevices, caves, overhangs, and sheltered lagoons.',
        'diet' => 'Small crustaceans and other tiny animals.',
        'fact' => 'Many cardinalfishes shelter by day and feed after dark.',
    ],
    'sweeper' => [
        'description' => 'A schooling reef fish that rests under ledges during the day and feeds in open water at night.',
        'habitat' => 'Caves, overhangs, and steep reef faces.',
        'diet' => 'Small planktonic animals caught in the water column.',
        'fact' => 'Large groups often gather beneath shaded reef ledges.',
    ],
    'coastal_predator' => [
        'description' => 'A coastal fish that hunts smaller animals along reef edges, sandy patches, or sheltered shore waters.',
        'habitat' => 'Shallow coastal waters, reef edges, estuaries, and nearby sandy or muddy bottoms.',
        'diet' => 'Small fishes, crustaceans, and other invertebrates.',
        'fact' => 'Coastal fish communities often move between reefs, seagrass beds, and sheltered bays.',
    ],
    'catshark' => [
        'description' => 'A small bottom-dwelling shark recorded from deeper water; the available occurrence record does not indicate how common it is locally.',
        'habitat' => 'Deep continental-shelf and upper-slope seabeds.',
        'diet' => 'Small bottom-living fishes and invertebrates.',
        'fact' => 'Small catsharks are distinct from the large, open-water sharks often seen near the surface.',
    ],
    'rabbitfish' => [
        'description' => 'A coastal rabbitfish that browses over reefs and vegetated shallows.',
        'habitat' => 'Reef flats, lagoons, seagrass areas, and other shallow coastal habitats.',
        'diet' => 'Algae and seagrasses.',
        'fact' => 'Rabbitfishes have venomous spines, so they should not be handled.',
    ],
    'flyingfish' => [
        'description' => 'A surface-swimming fish of warm open waters that can glide briefly above the sea when escaping danger.',
        'habitat' => 'Warm offshore surface waters, sometimes near islands and coastal currents.',
        'diet' => 'Small planktonic animals near the surface.',
        'fact' => 'Flyingfishes glide using enlarged pectoral fins; they do not fly by powered wing beats.',
    ],
    'damselfish' => [
        'description' => 'A small, active damselfish that feeds above the reef and uses nearby coral or rock for shelter.',
        'habitat' => 'Shallow coral reefs and lagoons.',
        'diet' => 'A mix of small planktonic animals and algae.',
        'fact' => 'Damselfishes are an important part of many shallow-reef food webs.',
    ],
    'wrasse' => [
        'description' => 'A reef wrasse that searches the bottom for small animals among coral, rubble, and seagrass.',
        'habitat' => 'Coral reefs, reef rubble, sandy patches, and seagrass edges.',
        'diet' => 'Small crustaceans, worms, and other invertebrates.',
        'fact' => 'Wrasses use their small mouths to pick prey from the reef surface.',
    ],
    'lionfish' => [
        'description' => 'A predatory reef fish with fan-shaped fins and venomous spines.',
        'habitat' => 'Coral reefs, lagoons, and rocky crevices.',
        'diet' => 'Small fishes and crustaceans.',
        'fact' => 'Its venomous fin spines are for defense; avoid touching the fish.',
    ],
    'clownfish' => [
        'description' => 'A reef fish that lives among the tentacles of a sea anemone, which provides shelter.',
        'habitat' => 'Shallow tropical reefs where suitable host anemones occur.',
        'diet' => 'Zooplankton, algae, and small food particles.',
        'fact' => 'Clownfishes and their host anemones have a well-known protective association.',
    ],
    'halfbeak' => [
        'description' => 'A slender surface fish that swims in schools in warm coastal water.',
        'habitat' => 'Nearshore surface waters, bays, and coastal lagoons.',
        'diet' => 'Small planktonic animals and tiny surface-dwelling prey.',
        'fact' => 'Halfbeaks have an elongated lower jaw that gives the group its common name.',
    ],
    'angelfish' => [
        'description' => 'A large, brightly patterned reef angelfish that shelters around coral formations.',
        'habitat' => 'Clear coral reefs, especially steep slopes and drop-offs.',
        'diet' => 'Sponges and other attached reef invertebrates.',
        'fact' => 'Angelfishes often change color patterns as they mature.',
    ],
    'moray' => [
        'description' => 'A reef eel that spends daylight hours in crevices and ambushes prey from shelter.',
        'habitat' => 'Coral reefs, rocky crevices, and reef rubble.',
        'diet' => 'Small fishes and crustaceans.',
        'fact' => 'Moray eels rely on narrow reef holes for shelter and ambush cover.',
    ],
    'moorish_idol' => [
        'description' => 'A distinctive reef fish with long dorsal and trailing fin extensions.',
        'habitat' => 'Coral reefs and rocky reef edges.',
        'diet' => 'Sponges, tunicates, and other small attached reef organisms.',
        'fact' => 'Its long, trailing dorsal filament makes it easy to recognize underwater.',
    ],
    'puffer' => [
        'description' => 'A reef-associated pufferfish with a rounded body and strong beak-like teeth.',
        'habitat' => 'Tropical reefs, lagoons, and adjacent sandy bottoms.',
        'diet' => 'Hard-shelled invertebrates such as mollusks and crustaceans.',
        'fact' => 'Pufferfishes should never be handled or eaten unless prepared by trained specialists.',
    ],
    'coral_trout' => [
        'description' => 'A reef predator that patrols coral heads and nearby sandy areas.',
        'habitat' => 'Coral reefs, reef slopes, and sandy channels.',
        'diet' => 'Small fishes and crustaceans.',
        'fact' => 'Reef predators help keep food webs balanced.',
    ],
    'sweetlips' => [
        'description' => 'A reef fish that searches the bottom for prey and is often seen near coral shelters.',
        'habitat' => 'Coral reefs, lagoons, and nearby sandy areas.',
        'diet' => 'Crustaceans, mollusks, and small fishes.',
        'fact' => 'Young sweetlips can have very different color patterns from adults.',
    ],
    'squirrelfish' => [
        'description' => 'A large-eyed reef fish that shelters in shaded crevices during the day.',
        'habitat' => 'Coral reefs, caves, and rocky overhangs.',
        'diet' => 'Small crustaceans and other invertebrates, often taken at night.',
        'fact' => 'Its large eyes are useful in the dim light of reef crevices.',
    ],
    'puffer_pelagic' => [
        'description' => 'A pufferfish recorded from offshore waters; the record is regional and does not establish local abundance.',
        'habitat' => 'Open water and deeper offshore habitats.',
        'diet' => 'Small fishes and invertebrates.',
        'fact' => 'Pufferfishes have fused teeth that form a strong beak.',
    ],
];

$regionalNote = 'OBIS returned an occurrence in the Marinduque-centred regional search area, but the available record did not confirm a specific locality. This is presented as regional evidence only, not as a verified site record or evidence of abundance.';
$fishSpecies = [
    ['Philippine damsel', 'Pomacentrus philippinus', 'LC', 'OBIS record near Marinduque at 13.5153°N, 122.1895°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/188488/1882347', 'reef_plankton', false],
    ['Jewelled blenny', 'Salarias fasciatus', 'LC', 'OBIS record near Marinduque at 13.5153°N, 122.1895°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/48342111/48387671', 'reef_algae', false],
    ['Eightband butterflyfish', 'Chaetodon octofasciatus', 'LC', 'OBIS record near Marinduque at 13.5365°N, 122.1307°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/165623/6070690', 'butterflyfish', false],
    ['Threespot dascyllus', 'Dascyllus trimaculatus', 'LC', 'OBIS record near Marinduque at 13.5652°N, 121.8422°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/188339/1860415', 'reef_plankton', false],
    ['Honeycomb filefish', 'Cantherhines pardalis', 'LC', 'OBIS record near Marinduque at 13.5153°N, 122.1895°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/70010275/115475527', 'coastal_predator', false],
    ['Fiveline cardinalfish', 'Cheilodipterus quinquelineatus', 'LC', 'OBIS record near Marinduque at 13.5376°N, 122.1311°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/193345/2225815', 'cardinalfish', false],
    ['Blackspot sweeper', 'Pempheris oualensis', 'LC', 'OBIS record near Marinduque at 13.5652°N, 121.8435°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/69545271/69545669', 'sweeper', false],
    ['Pygmy ribbontail catshark', 'Eridacnis radcliffei', 'LC', 'OBIS reports Marinduque-area records; the summary available did not identify a single verified site.', 'https://www.iucnredlist.org/species/161468/140358808', 'catshark', false],
    ['Jarbua terapon', 'Terapon jarbua', 'LC', 'OBIS record near Marinduque at 13.4730°N, 121.8283°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/166892/46643542', 'coastal_predator', false],
    ['Striped eel catfish', 'Plotosus lineatus', 'LC', 'OBIS reports a Marinduque-area record; the summary available did not identify a single verified site.', 'https://www.iucnredlist.org/species/166964/1157806', 'coastal_predator', false],
    ['Brassy chub', 'Kyphosus vaigiensis', 'LC', 'OBIS record near Marinduque at 13.5074°N, 121.8288°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/46086190/46664689', 'reef_algae', false],
    ['Sailfin flyingfish', 'Parexocoetus brachypterus', 'Not Evaluated', 'OBIS reports a Marinduque-area record; the summary available did not identify a single verified site.', 'https://www.fishbase.se/summary/Parexocoetus-brachypterus.html', 'flyingfish', false],
    ['Bluestripe snapper', 'Lutjanus kasmira', 'LC', 'OBIS record near Marinduque at 13.5248°N, 122.1183°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/194337/2314753', 'coral_trout', false],
    ['Staghorn damselfish', 'Amblyglyphidodon curacao', 'LC', 'OBIS record near Marinduque at 13.5652°N, 121.8433°E; a recorded occurrence, not evidence of abundance.', 'https://www.iucnredlist.org/species/188343/1861407', 'damselfish', false],
    ['Bigeye cardinalfish', 'Nectamia bandanensis', 'LC', 'OBIS reports a Marinduque-area record; the summary available did not identify a single verified site.', 'https://www.iucnredlist.org/species/193317/2220612', 'cardinalfish', false],
    ['Indo-Pacific sergeant', 'Abudefduf vaigiensis', 'LC', 'OBIS record from Romblon Harbor, Philippines; a nearby regional record, not a Marinduque-specific record.', 'https://www.iucnredlist.org/species/188609/1901550', 'damselfish', true],
    ['Striated surgeonfish', 'Ctenochaetus striatus', 'LC', 'OBIS record near Mindoro at 13.409638°N, 121.172605°E with 4,810 m coordinate uncertainty; regional evidence only, not a Marinduque-specific record.', 'https://www.iucnredlist.org/species/178012/1520757', 'reef_algae', true],
    ['Barhead spinefoot', 'Siganus virgatus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/117007442/117008803', 'rabbitfish', false],
    ['Double-lined fusilier', 'Pterocaesio digramma', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/20252379/65927523', 'reef_plankton', false],
    ['Scissortail sergeant', 'Abudefduf sexfasciatus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/188557/1892616', 'damselfish', false],
    ['Cocktail wrasse', 'Pteragogus flagellifer', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/187684/8600071', 'wrasse', false],
    ['Bluespotted cornetfish', 'Fistularia commersonii', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/18257780/115368874', 'coastal_predator', false],
    ['Raffles’ butterflyfish', 'Chaetodon rafflesii', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/165680/6089397', 'butterflyfish', false],
    ['Mental wrasse', 'Oxycheilinus mentalis', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/187735/8616212', 'wrasse', false],
    ['Vagabond butterflyfish', 'Chaetodon vagabundus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/165630/6073181', 'butterflyfish', false],
    ['Zebra turkeyfish', 'Dendrochirus zebra', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/69793953/69800937', 'lionfish', false],
    ['Shortfin flyingfish', 'Prognichthys brevipinnis', 'Not Evaluated', $regionalNote, 'https://www.fishbase.se/summary/Prognichthys-brevipinnis.html', 'flyingfish', false],
    ['Ocellaris clownfish', 'Amphiprion ocellaris', 'LC', 'OBIS record from Oriental Mindoro, Philippines; nearby regional evidence, not a Marinduque-specific record.', 'https://www.iucnredlist.org/species/188321/1857718', 'clownfish', true],
    ['Dussumier’s halfbeak', 'Hyporhamphus dussumieri', 'Not Evaluated', $regionalNote, 'https://www.fishbase.se/summary/Hyporhamphus-dussumieri.html', 'halfbeak', false],
    ['Zigzag wrasse', 'Halichoeres scapularis', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/187454/8539682', 'wrasse', false],
    ['Cheeklined wrasse', 'Oxycheilinus digramma', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/187466/8542656', 'wrasse', false],
    ['Spotfin lionfish', 'Pterois antennata', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/69799447/69801022', 'lionfish', false],
    ['Pastel-green wrasse', 'Halichoeres chloropterus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/187810/8635109', 'wrasse', false],
    ['Eastern triangular butterflyfish', 'Chaetodon baronessa', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/165692/6093453', 'butterflyfish', false],
    ['Regal angelfish', 'Pygoplites diacanthus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/165885/6157224', 'angelfish', false],
    ['Fimbriated moray', 'Gymnothorax fimbriatus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/195727/2408650', 'moray', false],
    ['Moorish idol', 'Zanclus cornutus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/69741115/69742744', 'moorish_idol', false],
    ['Red snout emperor', 'Lethrinus reticulatus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/155115/16722430', 'coastal_predator', false],
    ['Blacktail snapper', 'Lutjanus fulvus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/194377/2325959', 'coral_trout', false],
    ['Stellate puffer', 'Arothron stellatus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/193712/2264205', 'puffer', false],
    ['Golden damselfish', 'Amblyglyphidodon aureus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/188580/1896599', 'damselfish', false],
    ['Striped sweetlips', 'Plectorhinchus lineatus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/68377082/68379366', 'sweetlips', false],
    ['Saw-jawed monocle bream', 'Scolopsis ciliata', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/69539506/69539746', 'coastal_predator', false],
    ['Blackside hawkfish', 'Paracirrhites forsteri', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/67997879/115454368', 'coastal_predator', false],
    ['Blue and gold fusilier', 'Caesio caerulaurea', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/155097/4703967', 'reef_plankton', false],
    ['Pink squirrelfish', 'Sargocentron tiereoides', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/67903149/115446440', 'squirrelfish', false],
    ['Blunthead puffer', 'Sphoeroides pachygaster', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/190193/1943625', 'puffer_pelagic', false],
    ['Checkered snapper', 'Lutjanus decussatus', 'LC', $regionalNote, 'https://www.iucnredlist.org/species/155089/115270971', 'coral_trout', false],
];

$speciesRecords = [];
foreach ($fishSpecies as [$commonName, $scientificName, $statusCode, $occurrenceNote, $statusUrl, $profileKey, $regional]) {
    $profile = $fishProfiles[$profileKey];
    $speciesRecords[] = [
        'common_name' => $commonName,
        'scientific_name' => $scientificName,
        'category' => 'Fish',
        'description' => $profile['description'],
        'habitat' => $profile['habitat'],
        'diet' => $profile['diet'],
        'occurrence_note' => $occurrenceNote,
        'conservation_status' => $statusCode === 'LC' ? 'Least Concern (LC)' : $statusCode,
        'interesting_facts' => $profile['fact'],
        'references' => [
            marine_species_obis_url($scientificName, $regional),
            $statusUrl,
            marine_species_fishbase_url($scientificName),
        ],
    ];
}

$speciesRecords[] = [
    'common_name' => 'Horse’s hoof clam',
    'scientific_name' => 'Hippopus hippopus',
    'category' => 'Mollusk',
    'description' => 'A large reef clam that lives partly buried in shallow tropical reef habitats. Its Marinduque record has substantial coordinate uncertainty.',
    'habitat' => 'Shallow reef flats and lagoons in warm tropical seas.',
    'diet' => 'Filters tiny food particles from the water and receives nutrients from symbiotic algae in its tissues.',
    'occurrence_note' => 'OBIS reports a Marinduque administrative locality at 13.451016°N, 122.198559°E, dated 2016-04-29. The record has 31,020 m coordinate uncertainty, so the precise local site is not established.',
    'conservation_status' => 'Vulnerable (VU)',
    'interesting_facts' => 'Giant clams have microscopic algae living in their tissues and can also filter food from seawater.',
    'references' => [
        marine_species_obis_url('Hippopus hippopus'),
        'https://www.iucnredlist.org/species/10105/117405498',
    ],
];
$speciesRecords[] = [
    'common_name' => 'Small giant clam',
    'scientific_name' => 'Tridacna maxima',
    'category' => 'Mollusk',
    'description' => 'A reef clam that anchors in shallow tropical habitats and often shows bright mantle colors.',
    'habitat' => 'Shallow coral reefs, reef flats, and lagoons.',
    'diet' => 'Filters small food particles from the water and obtains nutrients from symbiotic algae.',
    'occurrence_note' => 'A historical OBIS record from “Marinduque Is, Ataa Is (Ata), 2km W of Kawayan” at 13.533°N, 121.850°E, dated 1976-01-04; coordinate uncertainty is 1,000 m. This confirms a recorded occurrence, not present-day abundance.',
    'conservation_status' => 'Least Concern (LC)',
    'interesting_facts' => 'The mantle contains symbiotic algae that use sunlight to provide part of the clam’s energy.',
    'references' => [
        marine_species_obis_url('Tridacna maxima'),
        'https://www.iucnredlist.org/species/22138/117405833',
    ],
];

if (!function_exists('curl_init')) {
    fwrite(STDERR, "PHP cURL is unavailable; species will be seeded with the image placeholder.\n");
}

$expectedRecordCount = 50;
$skipImages = in_array('--skip-images', $argv ?? [], true);
if (count($speciesRecords) !== $expectedRecordCount) {
    throw new RuntimeException('The curated seed data must contain exactly ' . $expectedRecordCount . ' records.');
}

$seededNames = [];
foreach ($speciesRecords as $record) {
    validate_species_seed_record($record);
    $nameKey = mb_strtolower(trim($record['scientific_name']), 'UTF-8');
    if (isset($seededNames[$nameKey])) {
        throw new RuntimeException('Duplicate scientific name in seed data: ' . $record['scientific_name']);
    }
    $seededNames[$nameKey] = true;
}

if (in_array('--validate-only', $argv ?? [], true)) {
    echo json_encode([
        'validated_records' => count($speciesRecords),
        'unique_scientific_names' => count($seededNames),
        'database_changed' => false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit;
}

$pdo = db();
$lockName = 'bantay_dagat_marine_species_seed';
$lock = $pdo->prepare('SELECT GET_LOCK(?, 5)');
$lock->execute([$lockName]);
if ((int) $lock->fetchColumn() !== 1) {
    throw new RuntimeException('Another marine-species seed process is running. Try again later.');
}

try {
    ensure_marine_species_seed_schema($pdo);

    $existingCategories = [
        'Chelonia mydas' => 'Marine Reptile',
        'Rhincodon typus' => 'Fish',
        'Dugong dugon' => 'Marine Mammal',
        'Eretmochelys imbricata' => 'Marine Reptile',
        'Amphiprioninae' => 'Fish',
    ];
    $fillExistingCategory = $pdo->prepare(
        "UPDATE marine_species
         SET category = ?
         WHERE scientific_name = ? AND (category IS NULL OR category = '')"
    );
    foreach ($existingCategories as $scientificName => $category) {
        $fillExistingCategory->execute([$category, $scientificName]);
    }
    $pdo->prepare(
        "UPDATE marine_species
         SET occurrence_note = COALESCE(NULLIF(occurrence_note, ''), ?)
         WHERE scientific_name = 'Amphiprioninae'"
    )->execute(['This existing entry is a subfamily-level group name, not a species; its occurrence and conservation status are not species-specific.']);

    $existingRows = $pdo->query('SELECT * FROM marine_species')->fetchAll();
    $existingByScientificName = [];
    foreach ($existingRows as $row) {
        if ($row['scientific_name'] !== null && trim($row['scientific_name']) !== '') {
            $existingByScientificName[mb_strtolower(trim($row['scientific_name']), 'UTF-8')] = $row;
        }
    }

    $insert = $pdo->prepare(
        'INSERT INTO marine_species
            (common_name, scientific_name, category, description, habitat, diet, occurrence_note,
             conservation_status, interesting_facts, species_image, image_source_url, reference_url)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?)'
    );
    $fillMetadata = $pdo->prepare(
        "UPDATE marine_species
         SET category = COALESCE(NULLIF(category, ''), ?),
             occurrence_note = COALESCE(NULLIF(occurrence_note, ''), ?),
             reference_url = COALESCE(NULLIF(reference_url, ''), ?),
             image_source_url = COALESCE(NULLIF(image_source_url, ''), ?)
         WHERE species_id = ?"
    );
    $fillImage = $pdo->prepare(
        "UPDATE marine_species
         SET species_image = ?, image_source_url = COALESCE(NULLIF(image_source_url, ''), ?)
         WHERE species_id = ? AND (species_image IS NULL OR species_image = '')"
    );

    $inserted = 0;
    $alreadyPresent = 0;
    $imagesFound = 0;
    foreach ($speciesRecords as $record) {
        $nameKey = mb_strtolower(trim($record['scientific_name']), 'UTF-8');
        $references = implode("\n", $record['references']);
        if (isset($existingByScientificName[$nameKey])) {
            $row = $existingByScientificName[$nameKey];
            $imageSource = $row['image_source_url'] ?: species_image_source_url($row['species_image']);
            $fillMetadata->execute([
                $record['category'],
                $record['occurrence_note'],
                $references,
                $imageSource,
                $row['species_id'],
            ]);
            $speciesId = (int) $row['species_id'];
            $alreadyPresent++;
            $needsImage = !$row['species_image'];
        } else {
            $insert->execute([
                $record['common_name'],
                $record['scientific_name'],
                $record['category'],
                $record['description'],
                $record['habitat'],
                $record['diet'],
                $record['occurrence_note'],
                $record['conservation_status'],
                $record['interesting_facts'],
                $references,
            ]);
            $speciesId = (int) $pdo->lastInsertId();
            $existingByScientificName[$nameKey] = ['species_id' => $speciesId, 'species_image' => null, 'image_source_url' => null];
            $inserted++;
            $needsImage = true;
        }

        if ($needsImage && function_exists('curl_init') && !$skipImages) {
            $image = find_species_image_online($record['common_name'], $record['scientific_name']);
            if ($image !== null) {
                $fillImage->execute([$image, species_image_source_url($image), $speciesId]);
                $existingByScientificName[$nameKey]['species_image'] = $image;
                $imagesFound++;
            }
        }

        echo ($inserted + $alreadyPresent) . '/' . $expectedRecordCount . ': ' . $record['common_name'] . PHP_EOL;
    }

    $total = (int) $pdo->query('SELECT COUNT(*) FROM marine_species')->fetchColumn();
    echo json_encode([
        'new_records' => $inserted,
        'existing_records_preserved' => $alreadyPresent,
        'images_found' => $imagesFound,
        'species_total' => $total,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
} finally {
    $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $release->execute([$lockName]);
}
