document.addEventListener('change', event => {
    if (event.target.matches('[data-image-preview]')) {
        const preview = document.querySelector(event.target.dataset.imagePreview);
        const file = event.target.files && event.target.files[0];
        if (preview && file) {
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        } else if (preview) {
            preview.removeAttribute('src');
            preview.style.display = 'none';
        }
    }
});

document.addEventListener('click', event => {
    const confirmation = event.target.closest('[data-confirm]');
    if (confirmation && !window.confirm(confirmation.dataset.confirm || 'Are you sure?')) {
        event.preventDefault();
    }
});
