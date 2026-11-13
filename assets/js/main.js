(() => {
    'use strict';

    const STORE_KEY = 'bantay-dagat-portal-v2';
    const statuses = ['Pending', 'Under Review', 'In Progress', 'Resolved', 'Rejected'];
    const categories = ['Illegal fishing', 'Pollution / waste', 'Marine wildlife', 'Habitat damage', 'Coastal hazard', 'Other'];
    const announcementCategories = ['Bantay Dagat Activities', 'Coastal Clean-Up Drives', 'Marine Conservation Programs', 'Environmental Announcements', 'Community Activities', 'Coastal Warnings', 'Emergency Advisories'];
    const initialData = {
        currentUser: null,
        adminSignedIn: false,
        reports: [],
        species: [
            { id: 1, name: 'Green sea turtle', scientific: 'Chelonia mydas', category: 'Marine reptiles', status: 'Endangered', image: 'assets/images/species-placeholder.svg', description: 'A marine turtle found in tropical and subtropical waters. It uses coastal habitats for feeding and nesting.', habitat: 'Seagrass beds and coral reefs.', occurrence: 'Found in tropical and subtropical seas worldwide, including the Indo-Pacific.', diet: 'Primarily seagrasses and algae as adults.', facts: 'Green sea turtles are named for the color of their body fat, not their shell.' },
            { id: 2, name: 'Whale shark', scientific: 'Rhincodon typus', category: 'Sharks and rays', status: 'Endangered', image: 'assets/images/species-placeholder.svg', description: 'The largest known fish, a slow-moving filter feeder that inhabits warm seas.', habitat: 'Open ocean and coastal waters.', occurrence: 'Occurs in tropical and warm-temperate seas around the world.', diet: 'Plankton and small fish.', facts: 'Despite its size, the whale shark feeds by filtering small organisms from the water.' },
            { id: 3, name: 'Giant clam', scientific: 'Tridacna gigas', category: 'Mollusks', status: 'Vulnerable', image: 'assets/images/species-placeholder.svg', description: 'A large reef-dwelling bivalve that lives in a symbiotic relationship with algae.', habitat: 'Shallow coral reefs in the Indo-Pacific.', occurrence: 'Native to shallow coral reef habitats in the Indo-Pacific region.', diet: 'Receives nutrients from symbiotic algae and filter feeding.', facts: 'Giant clams can live for many decades.' },
            { id: 4, name: 'Hawksbill sea turtle', scientific: 'Eretmochelys imbricata', category: 'Marine reptiles', status: 'Critically Endangered', image: 'assets/images/species-placeholder.svg', description: 'A reef-associated turtle recognized by its narrow beak and patterned shell.', habitat: 'Coral reefs and rocky coastal areas.', occurrence: 'Found in tropical waters of the Atlantic, Indian, and Pacific oceans.', diet: 'Sponges and other invertebrates.', facts: 'Its narrow beak helps it reach food in reef crevices.' },
            { id: 5, name: 'Blue sea star', scientific: 'Linckia laevigata', category: 'Invertebrates', status: 'Not evaluated', image: 'assets/images/species-placeholder.svg', description: 'A colorful sea star commonly associated with Indo-Pacific reef environments.', habitat: 'Reef flats and shallow lagoons.', occurrence: 'Widespread on shallow reefs across the Indo-Pacific.', diet: 'Biofilm and small organic material.', facts: 'Its color can vary from blue to orange or purple.' },
            { id: 6, name: 'Manta ray', scientific: 'Mobula alfredi', category: 'Sharks and rays', status: 'Vulnerable', image: 'assets/images/species-placeholder.svg', description: 'A large ray that swims through tropical seas and feeds by filtering plankton.', habitat: 'Coastal and offshore tropical waters.', occurrence: 'Found in tropical and subtropical waters of the Indo-Pacific.', diet: 'Zooplankton and small schooling fish.', facts: 'Manta rays have distinctive cephalic fins that help direct food toward the mouth.' }
        ],
        updates: [
            { id: 1, title: 'Community coastal care starts with small actions', category: 'Environmental Announcements', description: 'Residents can help keep shorelines healthy by disposing of waste responsibly and sharing accurate conservation information.', image: 'uploads/announcements/fdc8ed9f888d2deda826ca1f1e8a4362.jpg' },
            { id: 2, title: 'Coastal clean-up safety reminders', category: 'Coastal Clean-Up Drives', description: 'Use appropriate protective equipment, avoid hazardous objects, and follow local waste handling guidance during clean-up activities.', image: '' },
            { id: 3, title: 'Protect marine wildlife from a distance', category: 'Marine Conservation Programs', description: 'Observe marine animals without touching, feeding, chasing, or removing them from their natural habitat.', image: '' }
        ],
        activities: [],
        users: [],
        messages: [],
        inbox: []
    };

    const app = document.getElementById('app');
    const page = document.body.dataset.page || 'home';
    let data = JSON.parse(localStorage.getItem(STORE_KEY) || 'null');
    if (!data) {
        data = JSON.parse(JSON.stringify(initialData));
        localStorage.setItem(STORE_KEY, JSON.stringify(data));
    }

    const esc = value => String(value == null ? '' : value).replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[character]);
    const save = () => localStorage.setItem(STORE_KEY, JSON.stringify(data));
    const icon = name => `<i class="fa-solid fa-${name}" aria-hidden="true"></i>`;
    const dateText = value => value ? new Date(`${value}T12:00:00`).toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Date not provided';
    const statusClass = status => `status-${String(status || '').toLowerCase().replace(/\s+/g, '-')}`;
    const notice = text => {
        let node = document.getElementById('site-notice');
        if (!node) {
            node = document.createElement('div');
            node.id = 'site-notice';
            node.className = 'container alert alert-info mt-3';
            node.setAttribute('role', 'status');
            app.prepend(node);
        }
        node.textContent = text;
        node.hidden = false;
        node.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };
    const publicLinks = [
        ['index.html', 'Home', 'home'], ['report.html', 'Report an Issue', 'report'],
        ['map.html', 'Explore Map', 'map'], ['marine-species.html', 'Sea Creatures', 'marine-species'],
        ['updates.html', 'Latest Updates', 'updates'], ['about.html', 'About', 'about']
    ];
    const adminLinks = [
        ['dashboard.html', 'Dashboard', 'gauge'], ['manage-reports.html', 'Report Inbox', 'inbox'],
        ['manage-map.html', 'Map locations', 'map-location-dot'], ['manage-announcements.html', 'Announcements', 'bullhorn'],
        ['manage-species.html', 'Marine species', 'fish-fins'], ['manage-users.html', 'Users', 'users'],
        ['manage-activities.html', 'Clean-up activities', 'leaf'], ['messages.html', 'Messages', 'envelope'],
        ['profile.html', 'Admin profile', 'user-gear']
    ];

    function header() {
        const active = page === 'home' ? 'home' : page;
        return `<header class="site-header"><nav class="navbar navbar-expand-lg container" aria-label="Main navigation">
            <a class="navbar-brand brand" href="index.html"><span class="brand-mark">${icon('water')}</span><span>BANTAY-DAGAT<small>MARINDUQUE</small></span></a>
            <button class="navbar-toggler" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="navbar-collapse" id="main-nav"><div class="navbar-nav ms-auto align-items-lg-center">
                ${publicLinks.map(([href, label, key]) => `<a class="nav-link ${active === key ? 'active' : ''}" href="${href}">${label}</a>`).join('')}
                <a class="nav-link" href="user-inbox.html">Inbox</a>${data.currentUser ? `<a class="nav-link" href="profile.html">My Account</a><a class="nav-link" href="logout.html">Sign out</a>` : '<a class="nav-link" href="login.html">Login</a><a class="nav-link" href="register.html">Register</a>'}
                <a class="btn btn-primary rounded-pill nav-cta" href="report.html">Report an Issue ${icon('arrow-right')}</a>
            </div></div></nav></header>`;
    }

    function footer() {
        return `<footer class="site-footer"><div class="container footer-grid">
            <div><a class="brand brand-light" href="index.html"><span class="brand-mark">${icon('water')}</span><span>BANTAY-DAGAT<small>MARINDUQUE</small></span></a><p class="footer-copy mt-3">Protect our seas, preserve our future. Together, we can care for the waters and communities of Marinduque.</p></div>
            <div><h3>Explore</h3><a href="report.html">Report an issue</a><a href="report-status.html">Track a report</a><a href="map.html">Coastal map</a><a href="marine-species.html">Marine life</a></div>
            <div><h3>Learn &amp; connect</h3><a href="about.html">About Bantay Dagat</a><a href="clean-our-seas.html">Clean our seas</a><a href="updates.html">Latest updates</a><a href="about.html#contact">Contact form</a></div>
            <div><h3>Get in touch</h3><p>${icon('location-dot')} Marinduque, Philippines</p><p>Connect with the Bantay-Dagat community.</p><div class="social-links"><a href="https://www.facebook.com/search/top?q=Bantay-Dagat%20Marinduque" aria-label="Search Facebook for Bantay-Dagat Marinduque" rel="noopener noreferrer" target="_blank">${icon('facebook-f')}</a><a href="https://www.instagram.com/explore/tags/marinduque/" aria-label="Explore Marinduque on Instagram" rel="noopener noreferrer" target="_blank">${icon('instagram')}</a></div></div>
        </div><div class="container footer-bottom"><span>&copy; ${new Date().getFullYear()} Bantay-Dagat Marinduque</span><a href="about.html#privacy">Privacy &amp; responsible reporting</a><a href="admin/login.html">Administrator access</a></div></footer>
        <nav class="mobile-dock" aria-label="Quick navigation"><a href="index.html">${icon('house')}<span>Home</span></a><a href="report-status.html">${icon('clipboard-list')}<span>Reports</span></a><a href="map.html">${icon('map-location-dot')}<span>Map</span></a><a href="about.html">${icon('circle-info')}<span>About</span></a><a href="clean-our-seas.html">${icon('leaf')}<span>Clean seas</span></a></nav>`;
    }

    function pageHero(eyebrow, title, description) {
        return `<section class="page-hero"><div class="container"><span class="eyebrow eyebrow-light">${esc(eyebrow)}</span><h1>${esc(title)}</h1><p>${esc(description)}</p></div></section>`;
    }

    function speciesCard(item, index) {
        return `<div class="col-sm-6 col-lg-4"><article class="species-list-card"><a class="species-list-link" href="species-details.html?id=${encodeURIComponent(item.id)}"><div class="species-list-image"><img src="${esc(item.image || 'assets/images/species-placeholder.svg')}" alt="${esc(item.name)}" loading="lazy"></div><div class="species-card-body"><div class="d-flex flex-wrap justify-content-between gap-2"><span class="status-pill">${esc(item.category)}</span><span class="status-pill">${esc(item.status)}</span></div><h2 class="h5 mt-3">${esc(item.name)}</h2><i>${esc(item.scientific)}</i><p>${esc(item.description)}</p><span class="btn btn-outline-primary btn-sm rounded-pill">View details ${icon('arrow-right')}</span></div></a></article></div>`;
    }

    function updateCard(item, index) {
        const background = item.image ? ` style="background-image:linear-gradient(0deg,rgba(4,44,68,.2),rgba(4,44,68,.08)),url('${esc(item.image)}')"` : '';
        return `<div class="col-md-6 col-lg-4"><article class="update-card"><div class="update-image update-image-${index % 3 + 1}"${background}><span>${esc(item.category)}</span></div><div class="update-body"><h3>${esc(item.title)}</h3><p>${esc(item.description)}</p><a class="text-link" href="announcement-details.html?id=${encodeURIComponent(item.id)}">Read more ${icon('arrow-right')}</a></div></article></div>`;
    }

    function home() {
        return `<section class="hero"><div class="hero-art" aria-hidden="true"><div class="sun"></div><div class="hero-island"></div><div class="hero-wave wave-one"></div><div class="hero-wave wave-two"></div><span class="hero-fish fish-one">≈</span><span class="hero-fish fish-two">≈</span></div><div class="container hero-content"><div class="hero-copy"><span class="eyebrow eyebrow-light">${icon('location-dot')} MARINDUQUE, PHILIPPINES</span><h1>Protect our seas,<br><em>preserve our future.</em></h1><p>Help protect Marinduque's coastal environment through community reporting, local action, and ocean awareness.</p><div class="hero-actions"><a class="btn btn-aqua" href="report.html">Report an issue ${icon('arrow-right')}</a><a class="btn btn-ghost-light" href="map.html">${icon('map')} Explore the map</a></div><div class="hero-note"><span class="note-icon">${icon('heart')}</span> Small actions create a lasting tide of change.</div></div><div class="hero-badge"><span class="badge-icon">${icon('water')}</span><strong>One island.<br>One shared sea.</strong><small>Let's keep it thriving.</small></div></div></section>
        <section class="stats-strip"><div class="container stats-inner"><div class="stats-intro"><span class="eyebrow">COMMUNITY COASTAL CARE</span><h2>Every observation<br>can make a difference.</h2></div><div class="stat-item"><span class="stat-icon">${icon('binoculars')}</span><strong>Observe</strong><span>Notice changes along the coast</span></div><div class="stat-item"><span class="stat-icon stat-icon-aqua">${icon('file-circle-check')}</span><strong>Report</strong><span>Share coastal concerns</span></div><div class="stat-item"><span class="stat-icon stat-icon-green">${icon('leaf')}</span><strong>Protect</strong><span>Care for marine habitats</span></div></div></section>
        <section class="section-pad quick-section"><div class="container"><div class="section-heading"><div><span class="eyebrow">HOW CAN WE HELP?</span><h2>Make a difference today</h2></div><p>Find information and tools to help care for our coast.</p></div><div class="quick-grid">
        <a class="quick-card" href="report-status.html"><span class="quick-icon">${icon('magnifying-glass-location')}</span><span><b>Track a report</b><small>Check your report's progress</small></span>${icon('arrow-right')}</a>
        <a class="quick-card" href="map.html"><span class="quick-icon aqua">${icon('map-location-dot')}</span><span><b>Coastal map</b><small>Explore reported locations</small></span>${icon('arrow-right')}</a>
        <a class="quick-card" href="marine-species.html"><span class="quick-icon green">${icon('fish-fins')}</span><span><b>Sea creatures</b><small>Discover marine life</small></span>${icon('arrow-right')}</a>
        <a class="quick-card" href="updates.html"><span class="quick-icon coral">${icon('bullhorn')}</span><span><b>Latest updates</b><small>Conservation news and guidance</small></span>${icon('arrow-right')}</a></div></div></section>
        <section class="report-cta"><div class="container cta-inner"><div class="cta-icon">${icon('life-ring')}</div><div><span class="eyebrow eyebrow-light">SEE SOMETHING? SAY SOMETHING.</span><h2>Our coast needs your eyes.</h2><p>Share a coastal concern and help build awareness in your community.</p></div><a href="report.html" class="btn btn-aqua">Submit a report ${icon('arrow-right')}</a></div></section>
        <section class="section-pad species-section"><div class="container"><div class="section-heading"><div><span class="eyebrow">LIFE BELOW THE SURFACE</span><h2>Meet our marine neighbors</h2></div><a class="text-link" href="marine-species.html">Explore all species ${icon('arrow-right')}</a></div><div class="row g-4">${data.species.slice(0, 3).map(speciesCard).join('')}</div></div></section>
        <section class="section-pad updates-section"><div class="container"><div class="section-heading"><div><span class="eyebrow">FROM OUR COMMUNITY</span><h2>Latest from the coast</h2></div><a class="text-link" href="updates.html">All updates ${icon('arrow-right')}</a></div><div class="row g-4">${data.updates.slice(0, 3).map(updateCard).join('')}</div></div></section>`;
    }

    function about() {
        return `${pageHero('COMMUNITY-LED COASTAL CARE', 'About Bantay Dagat', 'Protecting the waters and shorelines of Marinduque through community participation, environmental awareness, and shared responsibility.')}
        <section class="content-section"><div class="container"><div class="row g-4"><div class="col-lg-7"><article class="surface-card prose"><span class="eyebrow">OUR PURPOSE</span><h2 class="mt-1">A healthier coast starts with all of us.</h2><p>Bantay Dagat Marinduque connects residents, fishers, visitors, and local response teams to promote coastal protection and environmental awareness.</p><h2>Our mission</h2><p>To promote the protection and preservation of Marinduque's coastal and marine resources through community participation and environmental awareness.</p><h2>Our vision</h2><p>A cleaner, safer, and more sustainable coastal environment for future generations.</p><h2>Our objectives</h2><ul><li>Make community incident reporting accessible and responsible.</li><li>Encourage collaboration to protect coastal habitats and livelihoods.</li><li>Share useful marine conservation information and local announcements.</li></ul></article></div><div class="col-lg-5"><div class="surface-card mb-4"><span class="eyebrow">HOW REPORTING WORKS</span><h2 class="h5 fw-bold">Share a coastal concern</h2><ol class="text-secondary small lh-lg ps-3"><li>Describe the issue and its location.</li><li>Keep the reference number provided after submission.</li><li>Use the reference number to review the report status.</li></ol><a class="btn btn-primary rounded-pill" href="report.html">Submit a report</a></div><div class="privacy-callout" id="privacy"><b>Privacy &amp; responsible reporting</b><br>Please do not include sensitive personal information. For emergencies or immediate danger, contact local emergency services.</div></div></div>
        <div class="row g-4 mt-2"><div class="col-md-4"><div class="activity-card">${icon('water')}<h3 class="h6 fw-bold mt-3">Healthy seas</h3><p class="help-text mb-0">Marine ecosystems sustain biodiversity, livelihoods, and community well-being.</p></div></div><div class="col-md-4"><div class="activity-card">${icon('people-group')}<h3 class="h6 fw-bold mt-3">Community role</h3><p class="help-text mb-0">Local observations help identify issues early and guide responsible response.</p></div></div><div class="col-md-4"><div class="activity-card">${icon('seedling')}<h3 class="h6 fw-bold mt-3">Shared responsibility</h3><p class="help-text mb-0">Everyday choices can help protect coastal habitats.</p></div></div></div></div></section>
        <section class="content-section pt-0" id="contact"><div class="container"><div class="row justify-content-center"><div class="col-lg-8"><form class="surface-card" data-form="contact"><span class="eyebrow">CONTACT THE TEAM</span><h2 class="h4 fw-bold">Send an inquiry</h2><p class="help-text">For urgent concerns, contact local emergency services.</p><div class="row g-3"><div class="col-md-6"><label class="form-label" for="contact-name">Name</label><input class="form-control" id="contact-name" name="name" maxlength="120" required></div><div class="col-md-6"><label class="form-label" for="contact-email">Email</label><input class="form-control" id="contact-email" name="email" type="email" maxlength="190" required></div><div class="col-12"><label class="form-label" for="contact-subject">Subject</label><input class="form-control" id="contact-subject" name="subject" maxlength="180" required></div><div class="col-12"><label class="form-label" for="contact-message">Message</label><textarea class="form-control" id="contact-message" name="message" rows="4" required></textarea></div><div class="col-12"><button class="btn btn-primary rounded-pill" type="submit">Send inquiry</button></div></div></form></div></div></div></section>`;
    }

    function reportForm() {
        return `<div class="row g-4"><div class="col-lg-8"><form class="surface-card" data-form="report"><h2 class="h5 fw-bold mb-1">Incident details</h2><p class="help-text mb-4">Fields marked <span class="field-required">*</span> are required. Please do not include personal or sensitive information.</p><div class="row g-3">
        <div class="col-12"><label class="form-label" for="report-title">Report title <span class="field-required">*</span></label><input class="form-control" id="report-title" name="title" maxlength="200" required placeholder="e.g. Fishing net left near the reef"></div>
        <div class="col-md-6"><label class="form-label" for="report-category">Incident category <span class="field-required">*</span></label><select class="form-select" id="report-category" name="category" required><option value="">Select category</option>${categories.map(item => `<option>${esc(item)}</option>`).join('')}</select></div>
        <div class="col-md-6"><label class="form-label" for="report-barangay">Barangay <span class="field-required">*</span></label><input class="form-control" id="report-barangay" name="barangay" required maxlength="120"></div>
        <div class="col-12"><label class="form-label" for="report-location">Specific location <span class="field-required">*</span></label><input class="form-control" id="report-location" name="location" required maxlength="255" placeholder="Beach, landmark, or nearby area"></div>
        <div class="col-12"><label class="form-label" for="report-description">Description <span class="field-required">*</span></label><textarea class="form-control" id="report-description" name="description" rows="5" maxlength="10000" required></textarea></div>
        <div class="col-md-6"><label class="form-label" for="report-date">Date of incident <span class="field-required">*</span></label><input class="form-control" id="report-date" type="date" name="date" required></div>
        <div class="col-md-6"><label class="form-label" for="report-time">Time of incident <span class="field-required">*</span></label><input class="form-control" id="report-time" type="time" name="time" required></div>
        <div class="col-12"><label class="form-label" for="report-map">Incident location</label><div class="d-flex gap-2 flex-wrap mb-2"><button type="button" class="btn btn-outline-primary btn-sm rounded-pill" data-action="locate-me">${icon('location-crosshairs')} Use my location</button><span class="help-text align-self-center">Or tap the map to place a pin.</span></div><div id="report-map" class="map-box" aria-label="Map for choosing an optional report location"></div></div>
        <div class="col-md-6"><label class="form-label" for="latitude">Latitude</label><input class="form-control" name="latitude" id="latitude" inputmode="decimal" placeholder="Select on map"></div><div class="col-md-6"><label class="form-label" for="longitude">Longitude</label><input class="form-control" name="longitude" id="longitude" inputmode="decimal" placeholder="Select on map"></div>
        </div><button class="btn btn-primary rounded-pill mt-4" type="submit">Submit report ${icon('arrow-right')}</button></form></div>
        <div class="col-lg-4"><aside class="surface-card mb-3"><h2 class="h6 fw-bold">${icon('shield-heart')} Report responsibly</h2><p class="help-text mb-0">If there is immediate danger, contact local authorities directly. Do not approach unsafe situations. Reporter contact details are not requested.</p></aside><aside class="surface-card"><h2 class="h6 fw-bold">Track your report</h2><p class="help-text mb-0">After submitting, use the reference number to view the report status.</p><a class="text-link d-inline-block mt-3" href="report-status.html">Track a report ${icon('arrow-right')}</a></aside></div></div>`;
    }

    function reportStatus() {
        const reference = new URLSearchParams(location.search).get('reference') || '';
        const report = data.reports.find(item => item.reference.toLowerCase() === reference.trim().toLowerCase());
        const steps = report && report.status === 'Rejected' ? statuses.slice(0, 2).concat('Rejected') : statuses.slice(0, 4);
        const stepMarkup = report ? `<div class="tracker">${steps.map((step, index) => {
            const currentIndex = steps.indexOf(report.status);
            const done = index < currentIndex;
            return `<div class="tracker-step ${done ? 'done' : ''} ${index === currentIndex ? 'current' : ''}"><span>${done ? icon('check') : index + 1}</span>${esc(step)}</div>`;
        }).join('')}</div>` : '';
        return `${pageHero('REPORT FOLLOW-UP', 'Track a report', 'Enter the reference number provided when the report was submitted.')}
        <section class="content-section"><div class="container" style="max-width:850px"><form class="surface-card mb-4" data-form="tracking"><label class="form-label" for="reference">Report reference number</label><div class="d-flex gap-2 flex-wrap"><input class="form-control flex-grow-1" id="reference" name="reference" value="${esc(reference)}" placeholder="BDM-20261113-A8F42K" required><button class="btn btn-primary rounded-pill" type="submit">${icon('magnifying-glass')} Search reports</button></div><div class="help-text mt-2">Enter the reference number issued with your report.</div></form>
        ${reference && !report ? '<div class="alert alert-info" role="status">No report was found with that reference number. Check the number and try again.</div>' : ''}
        ${report ? `<article class="surface-card"><div class="d-flex justify-content-between align-items-start gap-3 flex-wrap"><div><span class="eyebrow">REPORT REFERENCE</span><h2 class="h4 fw-bold mb-1">${esc(report.reference)}</h2><p class="text-secondary small mb-0">Submitted ${dateText(report.date)}</p></div><span class="status-pill ${statusClass(report.status)}">${esc(report.status)}</span></div>${stepMarkup}<hr><div class="row g-3"><div class="col-md-6"><small class="help-text">Report title</small><div class="fw-semibold">${esc(report.title)}</div></div><div class="col-md-6"><small class="help-text">Category</small><div class="fw-semibold">${esc(report.category)}</div></div><div class="col-md-6"><small class="help-text">Location</small><div class="fw-semibold">${esc(report.location)}, ${esc(report.barangay)}</div></div><div class="col-md-6"><small class="help-text">Incident date</small><div class="fw-semibold">${dateText(report.date)}</div></div><div class="col-12"><small class="help-text">Status note</small><p class="mb-0">${esc(report.remarks || 'Your report is pending review.')}</p></div></div></article>` : ''}
        </div></section>`;
    }

    function reportListCard(report, admin = false) {
        const statusInput = admin ? `<label class="visually-hidden" for="report-status-${report.id}">Update ${esc(report.reference)} status</label><select class="form-select form-select-sm" id="report-status-${report.id}" data-report-status="${report.id}">${statuses.map(status => `<option ${status === report.status ? 'selected' : ''}>${esc(status)}</option>`).join('')}</select>` : '';
        return `<article class="surface-card report-card mb-3"><div class="d-flex justify-content-between gap-3 flex-wrap"><div><span class="eyebrow">${esc(report.reference || 'REPORT')}</span><h3 class="h6 fw-bold mb-1">${esc(report.title)}</h3><p class="small text-secondary mb-0">${esc(report.category)} · ${esc(report.location)}, ${esc(report.barangay)} · ${dateText(report.date)}</p></div><span class="status-pill ${statusClass(report.status)}">${esc(report.status)}</span></div>${admin ? `<div class="d-flex align-items-center gap-2 mt-3">${statusInput}<button class="btn btn-outline-danger btn-sm" data-action="delete-report" data-id="${report.id}">Delete report</button></div>` : ''}</article>`;
    }

    function mapPage(admin = false) {
        const filterFields = `<div class="row g-3 align-items-end mb-3"><div class="col-md-4"><label class="form-label" for="map-category">Incident category</label><select class="form-select" id="map-category"><option value="">All categories</option>${categories.map(value => `<option>${esc(value)}</option>`).join('')}</select></div><div class="col-md-3"><label class="form-label" for="map-status">Report status</label><select class="form-select" id="map-status"><option value="">All statuses</option>${statuses.slice(0, 4).map(value => `<option>${esc(value)}</option>`).join('')}</select></div><div class="col-md-5"><label class="form-label" for="map-search">Search area</label><div class="input-group"><input class="form-control" id="map-search" placeholder="Barangay or place name"><button class="btn btn-outline-primary" id="map-search-button" type="button">Find</button></div></div></div>`;
        return `${pageHero(admin ? 'COASTAL MONITORING' : 'A COMMUNITY VIEW OF OUR SHARED COAST', admin ? 'Manage map locations' : 'Coastal monitoring map', 'Explore reported coastal locations across Marinduque.')}
        <section class="content-section"><div class="container"><div class="surface-card">${filterFields}<div id="${admin ? 'admin-map' : 'coastal-map'}" class="map-box" aria-label="Coastal monitoring map"></div><div class="d-flex justify-content-between gap-3 flex-wrap mt-3"><div class="map-legend"><span><i class="fa-solid fa-location-dot marker-blue"></i> Received / under review</span><span><i class="fa-solid fa-location-dot marker-orange"></i> In progress</span><span><i class="fa-solid fa-location-dot marker-green"></i> Resolved</span></div><small class="help-text" id="map-count" aria-live="polite">0 reported locations</small></div><div class="location-list mt-3" id="location-list"></div></div></div></section>`;
    }

    function speciesDirectory() {
        const query = new URLSearchParams(location.search);
        const search = (query.get('q') || '').toLowerCase();
        const category = query.get('category') || '';
        const matches = data.species.filter(item => (!search || `${item.name} ${item.scientific} ${item.category}`.toLowerCase().includes(search)) && (!category || item.category === category));
        const options = [...new Set(data.species.map(item => item.category))];
        return `${pageHero('OUR ISLAND’S MARINE NEIGHBORS', 'Discover sea creatures', 'Explore marine species and learn about their habitats, diets, and conservation status.')}
        <section class="content-section"><div class="container"><form class="surface-card mb-4" data-form="species-filter"><div class="row g-3 align-items-end"><div class="col-md-6"><label class="form-label" for="species-search">Search species</label><input class="form-control" id="species-search" name="q" value="${esc(query.get('q') || '')}" placeholder="Common or scientific name"></div><div class="col-md-4"><label class="form-label" for="species-category">Category</label><select class="form-select" id="species-category" name="category"><option value="">All categories</option>${options.map(item => `<option ${item === category ? 'selected' : ''}>${esc(item)}</option>`).join('')}</select></div><div class="col-md-2 d-grid"><button class="btn btn-primary rounded-pill" type="submit">Search</button></div></div>${search || category ? '<a class="small text-link d-inline-block mt-3" href="marine-species.html">Clear filters</a>' : ''}</form><p class="small text-secondary">${matches.length} species</p><div class="row g-4">${matches.map(speciesCard).join('') || '<div class="col-12"><div class="empty-note">No species matched these filters.</div></div>'}</div><div class="privacy-callout mt-5"><b>Conservation matters</b><br>Respect marine wildlife and its habitat. Consult authoritative sources for regional conservation information.</div></div></section>`;
    }

    function speciesDetails() {
        const item = data.species.find(species => String(species.id) === new URLSearchParams(location.search).get('id'));
        if (!item) return `${pageHero('MARINE SPECIES GUIDE', 'Species not found', 'Browse the marine species directory.')}<section class="content-section"><div class="container"><div class="empty-note">This species is unavailable. <a class="text-link" href="marine-species.html">View all species</a></div></div></section>`;
        return `${pageHero('MARINE SPECIES GUIDE', item.name, item.scientific)}<section class="content-section"><div class="container" style="max-width:900px"><article class="surface-card"><div class="detail-image mb-4"><img class="species-detail-photo" src="${esc(item.image)}" alt="${esc(item.name)}"></div><div class="d-flex flex-wrap gap-2"><span class="status-pill">${esc(item.category)}</span><span class="status-pill">${esc(item.status)}</span></div><div class="prose"><p class="mt-3">${esc(item.description)}</p><h2>Habitat</h2><p>${esc(item.habitat)}</p><h2>Occurrence</h2><p>${esc(item.occurrence)}</p><h2>Diet</h2><p>${esc(item.diet)}</p><h2>Interesting facts</h2><p>${esc(item.facts)}</p></div><a class="text-link" href="marine-species.html">${icon('arrow-left')} Back to the directory</a></article></div></section>`;
    }

    function updatesPage() {
        const query = new URLSearchParams(location.search);
        const term = (query.get('q') || '').toLowerCase();
        const category = query.get('category') || '';
        const matches = data.updates.filter(item => (!term || `${item.title} ${item.description}`.toLowerCase().includes(term)) && (!category || item.category === category));
        return `${pageHero('NEWS FROM OUR COAST', 'Latest updates', 'Conservation news, community activities, and coastal care guidance.')}
        <section class="content-section"><div class="container"><form class="surface-card mb-4" data-form="updates-filter"><div class="row g-3 align-items-end"><div class="col-md-6"><label class="form-label" for="updates-search">Search updates</label><input class="form-control" id="updates-search" name="q" value="${esc(query.get('q') || '')}" placeholder="Search title or description"></div><div class="col-md-4"><label class="form-label" for="updates-category">Category</label><select class="form-select" id="updates-category" name="category"><option value="">All categories</option>${announcementCategories.map(value => `<option ${category === value ? 'selected' : ''}>${esc(value)}</option>`).join('')}</select></div><div class="col-md-2 d-grid"><button class="btn btn-primary rounded-pill" type="submit">Search</button></div></div></form><div class="row g-4">${matches.map(updateCard).join('') || '<div class="col-12"><div class="empty-note">No updates match your search.</div></div>'}</div></div></section>`;
    }

    function announcementDetails() {
        const item = data.updates.find(update => String(update.id) === new URLSearchParams(location.search).get('id'));
        if (!item) return `${pageHero('LATEST UPDATES', 'Update not found', 'Browse the latest coastal updates.')}<section class="content-section"><div class="container"><div class="empty-note">This update is unavailable. <a class="text-link" href="updates.html">Browse updates</a></div></div></section>`;
        const details = [item.category, item.date && dateText(item.date), 'Bantay Dagat'].filter(Boolean).join(' · ');
        return `${pageHero('LATEST UPDATES', item.title, details)}<section class="content-section"><div class="container" style="max-width:850px"><article class="surface-card">${item.image ? `<img src="${esc(item.image)}" alt="" class="w-100 rounded-3 mb-4" style="max-height:430px;object-fit:cover">` : ''}<div class="prose"><p>${esc(item.description)}</p></div><hr><a class="text-link" href="updates.html">${icon('arrow-left')} Back to updates</a></article></div></section>`;
    }

    function cleanSeas() {
        const tips = [
            ['recycle', 'Dispose of waste properly', 'Sort and secure waste, avoid single-use plastics where practical, and never leave trash on beaches or waterways.'],
            ['broom', 'Join coastal clean-ups', 'Take part in organized clean-ups, use appropriate safety gear, and follow local waste handling guidance.'],
            ['fish-fins', 'Respect marine animals', 'Observe wildlife from a distance. Do not touch, feed, chase, or remove marine animals and corals.'],
            ['anchor', 'Fish responsibly', 'Follow local rules and seasonal restrictions. Report suspected destructive or illegal fishing activity safely.'],
            ['leaf', 'Protect coastal habitats', 'Avoid damaging mangroves, seagrass beds, reefs, and nesting areas. Leave natural habitats undisturbed.'],
            ['bullhorn', 'Share awareness', 'Share accurate conservation information and encourage neighbors to care for local waterways.']
        ];
        return `${pageHero('SMALL CHOICES. HEALTHIER SHORES.', 'Clean our seas', 'Practical ways to care for our coast and the marine life that depends on it.')}
        <section class="content-section"><div class="container"><div class="row g-3">${tips.map(([symbol, title, description]) => `<div class="col-md-6 col-lg-4"><article class="activity-card">${icon(symbol)}<h2 class="h6 fw-bold mt-3">${title}</h2><p class="help-text mb-0">${description}</p></article></div>`).join('')}</div><div class="mt-5"><span class="eyebrow">GET INVOLVED</span><h2 class="h3 fw-bold mb-3">Upcoming coastal activities</h2><div class="row g-3">${data.activities.filter(item => item.status === 'Upcoming').map(item => `<div class="col-md-6 col-lg-4"><article class="activity-card"><span class="status-pill">Upcoming activity</span><h3 class="h6 fw-bold mt-3">${esc(item.title)}</h3><p class="help-text">${esc(item.description)}</p><div class="small text-secondary">${icon('calendar')} ${dateText(item.date)}<br>${icon('location-dot')} ${esc(item.location)}</div></article></div>`).join('') || '<div class="col-12"><div class="empty-note">No upcoming activities are listed.</div></div>'}</div></div></div></section>`;
    }

    function authPage(kind) {
        if (kind === 'login') return `<section class="admin-login"><div class="surface-card admin-login-card"><span class="eyebrow">COMMUNITY COASTAL WATCH</span><h1 class="h3 fw-bold mb-2">Welcome back</h1><p class="text-secondary small mb-4">Enter your email address to continue to your profile.</p><form data-form="login"><div class="mb-3"><label class="form-label" for="identifier">Email address</label><input class="form-control" id="identifier" name="identifier" type="email" required autocomplete="email"></div><button class="btn btn-primary rounded-pill w-100" type="submit">Continue</button></form><p class="small text-center mt-4 mb-0">New here? <a class="text-link" href="register.html">Create a profile</a></p></div></section>`;
        if (kind === 'register') return `<section class="admin-login"><div class="surface-card admin-login-card"><span class="eyebrow">COMMUNITY COASTAL WATCH</span><h1 class="h3 fw-bold mb-2">Create a profile</h1><p class="text-secondary small mb-4">Join the community coastal watch.</p><form data-form="register"><div class="mb-3"><label class="form-label" for="full-name">Full name</label><input class="form-control" id="full-name" name="name" maxlength="150" required autocomplete="name"></div><div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" maxlength="190" required autocomplete="email"></div><div class="mb-3"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" minlength="3" maxlength="60" pattern="[A-Za-z0-9][A-Za-z0-9._\\-]{2,59}" required autocomplete="username"></div><button class="btn btn-primary rounded-pill w-100" type="submit">Create profile</button></form><p class="small text-center mt-4 mb-0">Already have a profile? <a class="text-link" href="login.html">Continue</a></p></div></section>`;
        return '<section class="admin-login"><div class="surface-card admin-login-card text-center"><span class="stat-icon stat-icon-green mx-auto mb-3"><i class="fa-solid fa-check"></i></span><h1 class="h4 fw-bold">You are signed out</h1><p class="text-secondary">Your session has ended.</p><a class="btn btn-primary rounded-pill" href="index.html">Return home</a></div></section>';
    }

    function logoutPage() {
        data.currentUser = null;
        save();
        return authPage('logout');
    }

    function profilePage() {
        const user = data.currentUser || { name: '', email: '', username: '' };
        return `${pageHero('COMMUNITY COASTAL WATCH', 'My Account', 'Review and update your profile.')}
        <section class="content-section"><div class="container"><div class="surface-card mx-auto" style="max-width:720px"><h2 class="h4 fw-bold mb-4">Profile information</h2><form data-form="profile"><div class="mb-3"><label class="form-label" for="profile-name">Full name</label><input class="form-control" id="profile-name" name="name" maxlength="150" value="${esc(user.name)}" required></div><div class="mb-3"><label class="form-label" for="profile-email">Email address</label><input class="form-control" id="profile-email" name="email" type="email" maxlength="190" value="${esc(user.email)}" required></div><div class="mb-4"><label class="form-label" for="profile-username">Username</label><input class="form-control" id="profile-username" name="username" minlength="3" maxlength="60" value="${esc(user.username)}" required></div><button class="btn btn-primary rounded-pill" type="submit">Save profile</button></form></div></div></section>`;
    }

    function inboxPage() {
        return `${pageHero('PRIVATE ACCOUNT MESSAGES', 'My Inbox', 'Review messages and report updates.')}
        <section class="content-section"><div class="container"><div class="surface-card"><div class="d-flex justify-content-between align-items-center gap-3 mb-4"><h2 class="h5 fw-bold mb-0">Messages</h2><span class="status-pill">${data.inbox.filter(item => !item.read).length} unread</span></div>${data.inbox.length ? `<div class="list-group list-group-flush">${data.inbox.map(item => `<article class="list-group-item px-0 py-3"><div class="d-flex justify-content-between align-items-start gap-3 flex-wrap"><div class="flex-grow-1"><div class="d-flex gap-2 flex-wrap mb-1"><span class="status-pill ${item.read ? '' : 'status-under-review'}">${item.read ? 'Read' : 'Unread'}</span></div><a class="fw-bold" href="user-inbox-message.html?id=${item.id}">${esc(item.subject)}</a><div class="small text-secondary mt-1">Report reference: <strong>${esc(item.reference)}</strong></div><p class="small text-secondary mb-0 mt-2">${esc(item.message)}</p></div><div class="d-flex align-items-center gap-2"><a class="btn btn-outline-primary btn-sm rounded-pill" href="user-inbox-message.html?id=${item.id}">Open</a>${item.read ? '' : `<button class="btn btn-outline-secondary btn-sm rounded-pill" data-action="mark-read" data-id="${item.id}">Mark read</button>`}</div></div></article>`).join('')}</div>` : '<div class="empty-note">No messages to display.</div>'}</div></div></section>`;
    }

    function inboxMessagePage() {
        const id = Number(new URLSearchParams(location.search).get('id')) || 1;
        const item = data.inbox.find(message => message.id === id);
        if (!item) return `${pageHero('PRIVATE ACCOUNT MESSAGE', 'Message not found', 'Choose a message from your inbox.')}<section class="content-section"><div class="container"><a href="user-inbox.html" class="text-link">Back to Inbox</a></div></section>`;
        return `${pageHero('PRIVATE ACCOUNT MESSAGE', item.subject, 'Report update')}
        <section class="content-section"><div class="container" style="max-width:850px"><article class="surface-card"><span class="eyebrow">REPORT REFERENCE</span><div class="input-group mb-3" style="max-width:420px"><input class="form-control fw-bold" id="report-reference" value="${esc(item.reference)}" readonly aria-label="Report reference"><button class="btn btn-outline-primary" data-action="copy-reference" type="button">Copy</button></div><div class="help-text" id="copy-status" aria-live="polite">Use this reference on the Report Status page.</div><hr><p>${esc(item.message)}</p><div class="d-flex gap-2 flex-wrap mt-4"><a class="btn btn-primary rounded-pill" href="report-status.html?reference=${encodeURIComponent(item.reference)}">Track this report</a><a class="btn btn-outline-secondary rounded-pill" href="user-inbox.html">Back to Inbox</a>${item.read ? '' : `<button class="btn btn-outline-primary rounded-pill" data-action="mark-read" data-id="${item.id}">Mark as read</button>`}</div></article></div></section>`;
    }

    function adminHeader(title, active) {
        return `<div class="admin-shell"><aside class="admin-sidebar" aria-label="Administrator navigation"><a class="brand" href="dashboard.html"><span class="brand-mark">${icon('water')}</span><span>BANTAY-DAGAT<small>ADMINISTRATION</small></span></a>${adminLinks.map(([href, label, symbol]) => `<a class="sidebar-link ${active === href ? 'active' : ''}" href="${href}">${icon(symbol)}${label}${href === 'manage-reports.html' ? `<span class="badge rounded-pill text-bg-danger ms-auto">${data.reports.filter(item => item.status === 'Pending').length}</span>` : ''}</a>`).join('')}<a class="sidebar-link mt-3" href="../index.html">${icon('arrow-up-right-from-square')}View public site</a><a class="sidebar-link" href="logout.html">${icon('right-from-bracket')}Log out</a></aside><main class="admin-content"><div class="admin-top"><div><span class="eyebrow mb-1">BANTAY-DAGAT MARINDUQUE</span><h1>${esc(title)}</h1></div><a class="small text-secondary" href="profile.html">${esc(data.currentAdmin || 'Administrator')}</a></div><div id="admin-notice" class="alert alert-success" role="status" hidden></div>`;
    }

    function adminDashboard() {
        const counts = Object.fromEntries(statuses.map(status => [status, data.reports.filter(item => item.status === status).length]));
        const stats = [['Total reports', data.reports.length, 'file-lines'], ...statuses.map((status, index) => [status, counts[status], ['hourglass-half', 'magnifying-glass', 'person-walking', 'circle-check', 'circle-xmark'][index]]), ['Registered users', data.users.length, 'users']];
        const byCategory = [...new Set(data.reports.map(item => item.category))].map(category => `<div class="admin-chart-row"><span>${esc(category)}</span><div class="admin-chart-track"><i style="width:${Math.max(8, data.reports.filter(item => item.category === category).length / Math.max(data.reports.length, 1) * 100)}%"></i></div><strong>${data.reports.filter(item => item.category === category).length}</strong></div>`).join('');
        return `<div class="admin-stat-grid mb-4">${stats.map(([label, count, symbol]) => `<div class="admin-stat"><i class="fa-solid fa-${symbol}"></i><span>${label}</span><strong>${count}</strong></div>`).join('')}</div><section class="surface-card mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap"><div><span class="eyebrow">REPORT FOLLOW-UP</span><h2 class="h6 fw-bold mb-1">Report Inbox</h2><p class="small text-secondary mb-0">${counts.Pending} reports awaiting review.</p></div><a class="btn btn-primary rounded-pill" href="manage-reports.html">Open Report Inbox</a></section><div class="row g-3 mb-4"><div class="col-lg-6"><section class="surface-card"><h2 class="h6 fw-bold">Reports by category</h2>${byCategory || '<p class="help-text">No reports are available.</p>'}</section></div><div class="col-lg-6"><section class="surface-card"><h2 class="h6 fw-bold">Report status totals</h2><div class="admin-status-list">${statuses.map(status => `<div><span class="status-pill ${statusClass(status)}">${esc(status)}</span><strong>${counts[status]}</strong></div>`).join('')}</div></section></div></div><section class="surface-card"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h6 fw-bold mb-0">Recent incident reports</h2><a class="text-link" href="manage-reports.html">Open Report Inbox</a></div>${data.reports.slice().reverse().map(report => reportListCard(report, true)).join('') || '<div class="empty-note">No reports are available.</div>'}</section>`;
    }

    function adminReports() {
        return `<section class="surface-card mb-4"><div class="row g-2 align-items-end"><div class="col-md-7"><label class="form-label" for="admin-report-search">Search reports</label><input class="form-control" id="admin-report-search" placeholder="Reference, title, or barangay"></div><div class="col-md-3"><label class="form-label" for="admin-report-filter">Status filter</label><select class="form-select" id="admin-report-filter"><option value="">All statuses</option>${statuses.map(item => `<option>${esc(item)}</option>`).join('')}</select></div><div class="col-md-2 d-grid"><button class="btn btn-primary rounded-pill" data-action="filter-reports" type="button">Filter</button></div></div></section><div id="admin-reports-list">${data.reports.map(report => reportListCard(report, true)).join('') || '<div class="empty-note">No reports found.</div>'}</div>`;
    }

    function managerPage(kind) {
        const config = {
            announcements: { title: 'Manage announcements', key: 'updates', fields: [['title', 'Title', 'text', true], ['category', 'Category', 'select', true], ['description', 'Description', 'textarea', true], ['date', 'Publication date', 'date', true]] },
            species: { title: 'Manage marine species', key: 'species', fields: [['name', 'Common name', 'text', true], ['scientific', 'Scientific name', 'text', true], ['category', 'Category', 'text', true], ['status', 'Conservation status', 'text', true], ['description', 'Description', 'textarea', true], ['habitat', 'Habitat', 'textarea', false], ['occurrence', 'Occurrence note', 'textarea', false], ['diet', 'Diet', 'textarea', false], ['facts', 'Interesting facts', 'textarea', false]] },
            activities: { title: 'Manage clean-up activities', key: 'activities', fields: [['title', 'Activity title', 'text', true], ['description', 'Description', 'textarea', true], ['location', 'Location', 'text', true], ['date', 'Date', 'date', true], ['status', 'Status', 'select', true]] }
        }[kind];
        const entity = data[config.key];
        const fields = config.fields.map(([name, label, type, required]) => {
            const choices = name === 'category' && kind === 'announcements' ? announcementCategories : name === 'status' && kind === 'activities' ? ['Upcoming', 'Completed', 'Cancelled'] : [];
            const control = type === 'textarea' ? `<textarea class="form-control" id="entity-${name}" name="${name}" rows="3" ${required ? 'required' : ''}></textarea>` : type === 'select' ? `<select class="form-select" id="entity-${name}" name="${name}" required><option value="">Choose one</option>${choices.map(item => `<option>${esc(item)}</option>`).join('')}</select>` : `<input class="form-control" id="entity-${name}" name="${name}" type="${type}" ${required ? 'required' : ''}>`;
            return `<div class="mb-3"><label class="form-label" for="entity-${name}">${label}</label>${control}</div>`;
        }).join('');
        const rows = entity.map(item => `<article class="activity-card mb-3"><div class="d-flex justify-content-between align-items-start gap-2"><div><h3 class="h6 fw-bold">${esc(item.title || item.name)}</h3><p class="small text-secondary mb-1">${esc(item.category || item.scientific || item.location || '')}</p><p class="help-text mb-0">${esc(item.description || item.occurrence || '')}</p></div><div class="d-flex gap-2 flex-wrap"><button class="btn btn-outline-primary btn-sm" data-action="edit-entity" data-kind="${kind}" data-id="${item.id}">Edit</button><button class="btn btn-outline-danger btn-sm" data-action="delete-entity" data-kind="${kind}" data-id="${item.id}">Delete</button></div></div></article>`).join('');
        return `<div class="row g-3"><div class="col-xl-5"><form class="surface-card" data-form="entity" data-kind="${kind}"><h2 class="h6 fw-bold">Add ${kind === 'species' ? 'a species' : kind === 'activities' ? 'an activity' : 'an announcement'}</h2><input type="hidden" name="id" value="">${fields}<button class="btn btn-primary rounded-pill" type="submit">Save changes</button> <button class="btn btn-outline-secondary rounded-pill" type="reset">Clear form</button></form></div><div class="col-xl-7"><section class="surface-card"><h2 class="h6 fw-bold">Directory</h2>${rows || '<div class="empty-note">No entries are available.</div>'}</section></div></div>`;
    }

    function adminUsers() {
        return `<section class="surface-card"><div class="admin-table-wrap"><table class="table table-hover align-middle"><thead><tr><th>User</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead><tbody>${data.users.map(user => `<tr><td>${esc(user.name)}</td><td>${esc(user.email)}</td><td>${esc(user.status)}</td><td><button class="btn btn-outline-primary btn-sm" data-action="toggle-user" data-id="${user.id}">${user.status === 'Active' ? 'Deactivate' : 'Activate'}</button> <button class="btn btn-outline-danger btn-sm" data-action="delete-user" data-id="${user.id}">Delete</button></td></tr>`).join('') || '<tr><td colspan="4">No user profiles are available.</td></tr>'}</tbody></table></div></section>`;
    }

    function adminMessages() {
        return `<section class="surface-card"><h2 class="h6 fw-bold mb-3">Community inquiries</h2>${data.messages.map((item, index) => `<article class="activity-card mb-3"><div class="d-flex justify-content-between gap-3"><div><strong>${esc(item.subject)}</strong><p class="small mb-1">${esc(item.name)} · ${esc(item.email)}</p><p class="small mb-0">${esc(item.message)}</p></div><button class="btn btn-outline-danger btn-sm" data-action="delete-message" data-id="${index}">Remove</button></div></article>`).join('') || '<div class="empty-note">No inquiries are available.</div>'}</section>`;
    }

    function adminProfile() {
        return `<div class="row g-3"><div class="col-lg-7"><form class="surface-card" data-form="admin-profile"><h2 class="h6 fw-bold mb-3">Profile information</h2><div class="mb-3"><label class="form-label" for="admin-name">Display name</label><input class="form-control" id="admin-name" name="name" maxlength="120" value="${esc(data.currentAdmin || 'Administrator')}" required></div><div class="mb-3"><label class="form-label" for="admin-email">Email</label><input class="form-control" id="admin-email" name="email" type="email" value="admin@example.test" required></div><button class="btn btn-primary rounded-pill">Save display name</button></form></div><div class="col-lg-5"><div class="surface-card"><span class="eyebrow">ADMINISTRATOR PROFILE</span><h2 class="h5 fw-bold">${esc(data.currentAdmin || 'Administrator')}</h2><p class="text-secondary small">Bantay-Dagat Marinduque</p><a class="btn btn-outline-primary rounded-pill btn-sm" href="logout.html">Sign out</a></div></div></div>`;
    }

    function adminLogin(kind) {
        const isRegister = kind === 'register';
        return `<section class="admin-login"><div class="surface-card admin-login-card"><span class="eyebrow">BANTAY-DAGAT ADMINISTRATION</span><h1 class="h3 fw-bold">${isRegister ? 'Create administrator profile' : 'Administrator workspace'}</h1><p class="help-text mb-4">${isRegister ? 'Enter a display name for the administrator profile.' : 'Enter a display name to open the workspace.'}</p><form data-form="${isRegister ? 'admin-register' : 'admin-login'}"><div class="mb-4"><label class="form-label" for="admin-name">Display name</label><input class="form-control" id="admin-name" name="name" maxlength="120" required></div><button class="btn btn-primary rounded-pill w-100" type="submit">${isRegister ? 'Create profile' : 'Open dashboard'}</button></form><p class="small text-center mt-4 mb-0">${isRegister ? 'Already have a profile? <a class="text-link" href="login.html">Open workspace</a>' : 'Need an administrator profile? <a class="text-link" href="register.html">Create profile</a>'}<br><a class="text-link" href="../index.html">Back to public website</a></p></div></section>`;
    }

    function adminEvidence() {
        return `${pageHero('REPORT EVIDENCE', 'Report evidence', 'Review images attached to coastal reports.')}
        <section class="content-section"><div class="container"><div class="surface-card"><div class="empty-note">No evidence is available for this report.</div><a class="text-link d-inline-block mt-3" href="manage-reports.html">Back to reports</a></div></div></section>`;
    }

    function adminMainContent() {
        const adminPages = {
            'admin-dashboard': ['Dashboard', 'dashboard.html', adminDashboard],
            'admin-manage-reports': ['Report Inbox', 'manage-reports.html', adminReports],
            'admin-manage-map': ['Manage map locations', 'manage-map.html', () => mapPage(true)],
            'admin-manage-announcements': ['Manage announcements', 'manage-announcements.html', () => managerPage('announcements')],
            'admin-manage-species': ['Manage marine species', 'manage-species.html', () => managerPage('species')],
            'admin-manage-users': ['Manage users', 'manage-users.html', adminUsers],
            'admin-manage-activities': ['Manage clean-up activities', 'manage-activities.html', () => managerPage('activities')],
            'admin-messages': ['Messages and inquiries', 'messages.html', adminMessages],
            'admin-profile': ['Admin profile', 'profile.html', adminProfile]
        };
        if (adminPages[page]) {
            const [title, active, render] = adminPages[page];
            return adminHeader(title, active) + render() + '</main></div>';
        }
        if (page === 'admin-login' || page === 'admin-register') return `<header class="site-header"><nav class="navbar container"><a class="brand" href="../index.html"><span class="brand-mark">${icon('water')}</span><span>BANTAY-DAGAT<small>MARINDUQUE</small></span></a><a class="text-link" href="../index.html">Back to website</a></nav></header>${adminLogin(page === 'admin-register' ? 'register' : 'login')}`;
        if (page === 'admin-logout') {
            data.adminSignedIn = false;
            data.currentAdmin = '';
            save();
            return `<header class="site-header"><nav class="navbar container"><a class="brand" href="../index.html"><span class="brand-mark">${icon('water')}</span><span>BANTAY-DAGAT<small>MARINDUQUE</small></span></a></nav></header><section class="admin-login"><div class="surface-card admin-login-card text-center"><h1 class="h4 fw-bold">You are signed out</h1><p class="text-secondary">Your session has ended.</p><a class="btn btn-primary rounded-pill" href="login.html">Return to administrator access</a></div></section>`;
        }
        if (page === 'admin-evidence') return adminHeader('Report evidence', 'manage-reports.html') + adminEvidence() + '</main></div>';
        return '';
    }

    function render() {
        if (page.startsWith('admin-')) {
            app.innerHTML = adminMainContent();
            if (page === 'admin-manage-map') initMap('admin-map');
            return;
        }
        const content = {
            home,
            about,
            report: () => `${pageHero('COMMUNITY COASTAL WATCH', 'Report a coastal issue', 'Provide clear details and the location of the concern.')}<section class="content-section"><div class="container">${reportForm()}</div></section>`,
            'report-status': reportStatus,
            map: () => mapPage(false),
            'marine-species': speciesDirectory,
            'species-details': speciesDetails,
            updates: updatesPage,
            'announcement-details': announcementDetails,
            'clean-our-seas': cleanSeas,
            login: () => authPage('login'),
            register: () => authPage('register'),
            logout: logoutPage,
            profile: profilePage,
            'user-inbox': inboxPage,
            'user-inbox-message': inboxMessagePage
        }[page];
        app.innerHTML = header() + '<main>' + (content ? content() : home()) + '</main>' + footer();
        if (page === 'map') initMap('coastal-map');
        if (page === 'report') initReportMap();
    }

    function initMap(elementId) {
        const mapNode = document.getElementById(elementId);
        if (!mapNode) return;
        const locations = data.reports.filter(item => Number.isFinite(Number(item.latitude)) && Number.isFinite(Number(item.longitude)));
        const list = document.getElementById('location-list');
        const count = document.getElementById('map-count');
        if (!window.L) {
            mapNode.innerHTML = `<div class="map-fallback"><strong>Coastal monitoring map</strong><p>${locations.map(item => `${esc(item.barangay)} (${Number(item.latitude).toFixed(3)}, ${Number(item.longitude).toFixed(3)})`).join(' · ') || 'No reported locations are available.'}</p></div>`;
            if (count) count.textContent = `${locations.length} reported location${locations.length === 1 ? '' : 's'}`;
            if (list) list.innerHTML = locations.map(item => `<div class="location-item">${esc(item.reference)} · ${esc(item.location)}, ${esc(item.barangay)}</div>`).join('') || '<div class="empty-note">No reported locations are available.</div>';
            return;
        }
        const map = window.L.map(mapNode).setView([13.45, 121.95], 9);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        const markers = window.L.layerGroup().addTo(map);
        const category = document.getElementById('map-category');
        const status = document.getElementById('map-status');
        const apply = () => {
            markers.clearLayers();
            const selected = locations.filter(item => (!category || !category.value || item.category === category.value) && (!status || !status.value || item.status === status.value));
            selected.forEach(item => {
                const color = item.status === 'Resolved' ? '#33a278' : item.status === 'In Progress' ? '#e2a72e' : '#087db5';
                const markerIcon = window.L.divIcon({ className: 'custom-marker', html: `<i style="color:${color};font-size:27px;text-shadow:0 2px 4px #fff" class="fa-solid fa-location-dot"></i>`, iconSize: [25, 30], iconAnchor: [12, 29] });
                window.L.marker([Number(item.latitude), Number(item.longitude)], { icon: markerIcon }).bindPopup(`<b>${esc(item.reference)}</b><br>${esc(item.category)}<br>${esc(item.location)}, ${esc(item.barangay)}<br>${dateText(item.date)} · ${esc(item.status)}`).addTo(markers);
            });
            if (count) count.textContent = `${selected.length} reported location${selected.length === 1 ? '' : 's'}`;
            if (list) list.innerHTML = selected.map(item => `<div class="location-item"><strong>${esc(item.reference)}</strong> · ${esc(item.location)}, ${esc(item.barangay)} · ${esc(item.status)}</div>`).join('') || '<div class="empty-note">No reported locations are available.</div>';
        };
        if (category) category.addEventListener('change', apply);
        if (status) status.addEventListener('change', apply);
        const find = document.getElementById('map-search-button');
        if (find) find.addEventListener('click', () => {
            const value = document.getElementById('map-search').value.trim().toLowerCase();
            const match = locations.find(item => `${item.location} ${item.barangay}`.toLowerCase().includes(value));
            if (!value) {
                notice('Enter a barangay or place name.');
                return;
            }
            if (match) map.setView([Number(match.latitude), Number(match.longitude)], 13);
            else notice('No reported location matched your search.');
        });
        apply();
        setTimeout(() => map.invalidateSize(), 100);
    }

    function initReportMap() {
        const mapNode = document.getElementById('report-map');
        if (!mapNode || !window.L) {
            if (mapNode) mapNode.innerHTML = '<div class="map-fallback">Select a point on the map when map tiles are available, or enter latitude and longitude manually.</div>';
            return;
        }
        const map = window.L.map(mapNode).setView([13.45, 121.95], 9);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        let marker;
        const pin = (latitude, longitude) => {
            if (marker) marker.setLatLng([latitude, longitude]);
            else {
                marker = window.L.marker([latitude, longitude], { draggable: true }).addTo(map);
                marker.on('dragend', () => {
                    const point = marker.getLatLng();
                    document.getElementById('latitude').value = point.lat.toFixed(7);
                    document.getElementById('longitude').value = point.lng.toFixed(7);
                });
            }
            document.getElementById('latitude').value = Number(latitude).toFixed(7);
            document.getElementById('longitude').value = Number(longitude).toFixed(7);
        };
        map.on('click', event => pin(event.latlng.lat, event.latlng.lng));
        document.querySelector('[data-action="locate-me"]')?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                notice('Location services are unavailable in this browser.');
                return;
            }
            navigator.geolocation.getCurrentPosition(position => {
                map.setView([position.coords.latitude, position.coords.longitude], 14);
                pin(position.coords.latitude, position.coords.longitude);
            }, () => notice('Location permission was not available. Tap the map to set a pin.'));
        });
        setTimeout(() => map.invalidateSize(), 100);
    }

    function makeReference() {
        const day = new Date();
        const ymd = `${day.getFullYear()}${String(day.getMonth() + 1).padStart(2, '0')}${String(day.getDate()).padStart(2, '0')}`;
        return `BDM-${ymd}-${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
    }

    app.addEventListener('submit', event => {
        const form = event.target.closest('form[data-form]');
        if (!form) return;
        event.preventDefault();
        const values = Object.fromEntries(new FormData(form).entries());
        switch (form.dataset.form) {
            case 'tracking':
                location.href = `report-status.html?reference=${encodeURIComponent(String(values.reference || '').trim().toUpperCase())}`;
                break;
            case 'species-filter':
            case 'updates-filter': {
                const target = page === 'marine-species' ? 'marine-species.html' : 'updates.html';
                const params = new URLSearchParams();
                if (values.q) params.set('q', values.q);
                if (values.category) params.set('category', values.category);
                location.href = `${target}${params.size ? `?${params}` : ''}`;
                break;
            }
            case 'report': {
                const report = {
                    id: Date.now(),
                    reference: makeReference(),
                    title: values.title.trim(),
                    category: values.category,
                    location: values.location.trim(),
                    barangay: values.barangay.trim(),
                    description: values.description.trim(),
                    date: values.date,
                    status: 'Pending',
                    remarks: 'Your report is pending review.',
                    latitude: values.latitude ? Number(values.latitude) : null,
                    longitude: values.longitude ? Number(values.longitude) : null
                };
                data.reports.push(report);
                save();
                form.reset();
                notice(`Report reference created: ${report.reference}. Keep this number to check the report status.`);
                break;
            }
            case 'login': {
                const user = data.users.find(item => item.email.toLowerCase() === String(values.identifier).toLowerCase() || item.username === values.identifier) || { name: String(values.identifier).split('@')[0], email: String(values.identifier), username: '' };
                data.currentUser = { name: user.name, email: user.email, username: user.username || '' };
                save();
                location.href = 'profile.html';
                break;
            }
            case 'register': {
                const user = { id: Date.now(), name: values.name.trim(), email: values.email.trim(), username: values.username, status: 'Active' };
                data.users.push(user);
                data.currentUser = { name: user.name, email: user.email, username: user.username };
                save();
                location.href = 'profile.html';
                break;
            }
            case 'logout':
                data.currentUser = null;
                save();
                location.href = 'index.html';
                break;
            case 'profile':
                data.currentUser = { name: values.name.trim(), email: values.email.trim(), username: values.username.trim() };
                save();
                notice('Profile updated.');
                break;
            case 'contact':
                notice('Your inquiry is ready to copy and share with the Bantay-Dagat team.');
                break;
            case 'admin-login':
            case 'admin-register':
                data.adminSignedIn = true;
                data.currentAdmin = values.name || 'Administrator';
                save();
                location.href = 'dashboard.html';
                break;
            case 'admin-profile':
                data.currentAdmin = values.name.trim();
                save();
                notice('Administrator display name updated.');
                break;
            case 'entity': {
                const kind = form.dataset.kind;
                const collection = kind === 'announcements' ? data.updates : kind === 'species' ? data.species : data.activities;
                const record = Object.fromEntries(new FormData(form).entries());
                const current = collection.find(item => String(item.id) === String(record.id));
                if (current) Object.assign(current, record);
                else collection.unshift({ ...record, id: Date.now() });
                save();
                render();
                break;
            }
        }
    });

    app.addEventListener('change', event => {
        if (event.target.matches('[data-report-status]')) {
            const report = data.reports.find(item => String(item.id) === event.target.dataset.reportStatus);
            if (report) {
                report.status = event.target.value;
                report.remarks = 'Report status updated.';
                save();
                render();
            }
        }
    });

    app.addEventListener('click', async event => {
        const toggler = event.target.closest('.navbar-toggler');
        if (toggler) {
            const nav = document.getElementById('main-nav');
            const expanded = toggler.getAttribute('aria-expanded') === 'true';
            toggler.setAttribute('aria-expanded', String(!expanded));
            nav.classList.toggle('is-open', !expanded);
            return;
        }
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const id = Number(button.dataset.id);
        switch (button.dataset.action) {
            case 'locate-me':
                break;
            case 'mark-read': {
                const message = data.inbox.find(item => item.id === id);
                if (message) message.read = true;
                save();
                render();
                break;
            }
            case 'copy-reference': {
                const input = document.getElementById('report-reference');
                const status = document.getElementById('copy-status');
                try {
                    await navigator.clipboard.writeText(input.value);
                    status.textContent = 'Reference copied.';
                } catch (error) {
                    status.textContent = 'Copy was unavailable. Select the reference and copy it manually.';
                }
                break;
            }
            case 'delete-report':
                if (window.confirm('Delete this report?')) {
                    data.reports = data.reports.filter(item => item.id !== id);
                    save();
                    render();
                }
                break;
            case 'filter-reports':
                filterAdminReports();
                break;
            case 'delete-entity': {
                const key = button.dataset.kind === 'species' ? 'species' : button.dataset.kind === 'activities' ? 'activities' : 'updates';
                data[key] = data[key].filter(item => item.id !== id);
                save();
                render();
                break;
            }
            case 'edit-entity': {
                const key = button.dataset.kind === 'species' ? 'species' : button.dataset.kind === 'activities' ? 'activities' : 'updates';
                const record = data[key].find(item => item.id === id);
                const form = document.querySelector('form[data-form="entity"]');
                if (record && form) {
                    Object.entries(record).forEach(([name, value]) => {
                        const field = form.elements.namedItem(name);
                        if (field) field.value = value;
                    });
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                break;
            }
            case 'toggle-user': {
                const user = data.users.find(item => item.id === id);
                if (user) user.status = user.status === 'Active' ? 'Inactive' : 'Active';
                save();
                render();
                break;
            }
            case 'delete-user':
                data.users = data.users.filter(item => item.id !== id);
                save();
                render();
                break;
            case 'delete-message':
                data.messages.splice(id, 1);
                save();
                render();
                break;
        }
    });

    function filterAdminReports() {
        const search = (document.getElementById('admin-report-search')?.value || '').toLowerCase();
        const status = document.getElementById('admin-report-filter')?.value || '';
        const matches = data.reports.filter(item => (!status || item.status === status) && (!search || `${item.reference} ${item.title} ${item.barangay} ${item.category}`.toLowerCase().includes(search)));
        const list = document.getElementById('admin-reports-list');
        if (list) list.innerHTML = matches.map(item => reportListCard(item, true)).join('') || '<div class="empty-note">No reports match these filters.</div>';
    }

    render();
})();
