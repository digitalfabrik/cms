function ig_media_field(id, post_id = 0) {
    let file_frame;
    let wp_media_post_id = wp.media.model.settings.post.id;

    const image_preview = document.getElementById('image-preview-' + id);
    const image_preview_wrapper = document.getElementById('image-preview-wrapper-' + id);
    const image_attachment_id = document.getElementById('image_attachment_id-' +  id);

    document.getElementById('upload_image_button-' + id).onclick=function(e){
        e.preventDefault();

        if ( file_frame ) {
            file_frame.uploader.uploader.param( 'post_id', post_id );
            file_frame.open();
            return;
        } else {
            wp.media.model.settings.post.id = post_id;
        }

        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select a image to upload',
            button: {
                text: 'Use this image',
            },
            multiple: false
        });

        file_frame.on( 'select', function() {
            let attachment = file_frame.state().get('selection').first().toJSON();

            image_preview.src = attachment.url;
            image_preview_wrapper.style.display = 'block';
            image_attachment_id.value = attachment.id;

            wp.media.model.settings.post.id = wp_media_post_id;
        });

        // Finally, open the modal
        file_frame.open();
    };
}

function ig_media_field_delete(id) {
    document.getElementById('image_delete-' + id).onclick=function(e){
        e.preventDefault();

        document.getElementById('image-preview-' + id).src = '';
        document.getElementById('image-preview-wrapper-' + id).style.display = 'none';
        document.getElementById('image_attachment_id-' +  id).value = '';
    };
}