<?php
require_once __DIR__ . '/_layout.php';

$id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$edit = null;
if ($id) {
    $statement = db()->prepare('SELECT * FROM marine_species WHERE species_id=?');
    $statement->execute([$id]);
    $edit = $statement->fetch() ?: null;
}

$items = db()->query(
    'SELECT species_id AS id, common_name, scientific_name, category, conservation_status
     FROM marine_species
     ORDER BY common_name'
)->fetchAll();

admin_header('Manage marine species');
?>
<div class="row g-3">
    <div class="col-xl-5">
        <form class="surface-card" action="../actions/admin.php" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_species">
            <input type="hidden" name="id" value="<?= (int) ($edit['species_id'] ?? 0) ?>">
            <h2 class="h6 fw-bold"><?= $edit ? 'Edit species' : 'Add species' ?></h2>

            <div class="mb-3">
                <label class="form-label" for="common-name">Common name</label>
                <input class="form-control" id="common-name" name="common_name" required maxlength="140" value="<?= e($edit['common_name'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="scientific-name">Scientific name</label>
                <input class="form-control" id="scientific-name" name="scientific_name" required maxlength="160" value="<?= e($edit['scientific_name'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="category">Category</label>
                <select class="form-select" id="category" name="category" required>
                    <option value="">Choose a category</option>
                    <?php foreach (marine_species_categories() as $category): ?>
                        <option value="<?= e($category) ?>" <?= ($edit['category'] ?? '') === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="conservation-status">Conservation status</label>
                <input class="form-control" id="conservation-status" name="conservation_status" maxlength="100" value="<?= e($edit['conservation_status'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required><?= e($edit['description'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="habitat">Habitat</label>
                <textarea class="form-control" id="habitat" name="habitat" rows="2"><?= e($edit['habitat'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="diet">Diet</label>
                <textarea class="form-control" id="diet" name="diet" rows="2"><?= e($edit['diet'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="occurrence-note">Marinduque / regional occurrence note</label>
                <textarea class="form-control" id="occurrence-note" name="occurrence_note" rows="3" required maxlength="2000"><?= e($edit['occurrence_note'] ?? '') ?></textarea>
                <div class="help-text mt-2">State whether the record is local, from nearby waters, or regional only. Do not claim a local sighting without a source.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="interesting-facts">Interesting facts</label>
                <textarea class="form-control" id="interesting-facts" name="interesting_facts" rows="2"><?= e($edit['interesting_facts'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="reference-url">References</label>
                <textarea class="form-control" id="reference-url" name="reference_url" rows="3" required maxlength="4000"><?= e($edit['reference_url'] ?? '') ?></textarea>
                <div class="help-text mt-2">Enter one or more HTTP/HTTPS reference URLs, one per line.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="species-image">Image URL <span class="text-secondary">(optional)</span></label>
                <input class="form-control" type="url" id="species-image" name="species_image" maxlength="255" value="<?= e($edit['species_image'] ?? '') ?>" placeholder="https://...">
                <div class="help-text mt-2">When blank on a new species, the system searches Wikimedia Commons for a licensed matching image. No upload is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="image-source-url">Image source / license page</label>
                <input class="form-control" type="url" id="image-source-url" name="image_source_url" maxlength="1000" value="<?= e($edit['image_source_url'] ?? '') ?>" placeholder="https://commons.wikimedia.org/wiki/File:...">
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary rounded-pill"><?= $edit ? 'Save changes' : 'Add species' ?></button>
                <?php if ($edit): ?><a class="btn btn-outline-secondary rounded-pill" href="manage-species.php">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="col-xl-7">
        <section class="surface-card">
            <h2 class="h6 fw-bold">Species directory</h2>
            <div class="admin-table-wrap">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Common name</th><th>Scientific name</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['common_name']) ?></td>
                            <td><i><?= e($item['scientific_name']) ?></i></td>
                            <td><?= e($item['category'] ?: 'Uncategorized') ?></td>
                            <td><?= e($item['conservation_status']) ?></td>
                            <td class="text-nowrap">
                                <a class="btn btn-outline-primary btn-sm" href="manage-species.php?edit=<?= (int) $item['id'] ?>">Edit</a>
                                <form class="d-inline" method="post" action="../actions/admin.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_species">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm" data-confirm="Delete this species record?">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$items): ?><tr><td colspan="5" class="text-secondary">No species have been added.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?php admin_footer(); ?>
