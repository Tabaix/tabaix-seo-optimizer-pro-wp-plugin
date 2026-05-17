(function($) {
    if (typeof tabaix_seo_seo_data === 'undefined') return;
    
    var ajaxUrl = tabaix_seo_seo_data.ajaxUrl;
    var nonce = tabaix_seo_seo_data.nonce;
    var postId = tabaix_seo_seo_data.postId;
    var postTitle = tabaix_seo_seo_data.postTitle;

    // Tabs
    $('.tabaix-seo-mb-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.tabaix-seo-mb-tab, .tabaix-seo-mb-content').removeClass('active');
        $(this).addClass('active');
        $('#tabaix-seo-tab-' + tab).addClass('active');
    });

    function showLoader(msg) {
        $('#tabaix-seo-mb-loader span').text(msg || 'Thinking...');
        $('#tabaix-seo-mb-loader').css('display', 'flex');
    }
    function hideLoader() { $('#tabaix-seo-mb-loader').hide(); }

    // SEO Meta
    $('#tabaix-seo-btn-gen-meta').on('click', function() {
        showLoader('Generating SEO meta...');
        $.post(ajaxUrl, {
            action: 'tabaix_seo_generate_meta',
            nonce: nonce,
            title: postTitle,
            keyword: $('#tabaix_seo_focus_keyword').val()
        }, function(res) {
            hideLoader();
            if(res.success) {
                if(res.data.seo_title) $('#tabaix_seo_seo_title').val(res.data.seo_title);
                if(res.data.meta_description) $('#tabaix_seo_meta_description').val(res.data.meta_description);
            }
        });
    });

    // AI Image
    $('#tabaix-seo-btn-gen-img').on('click', function() {
        var prompt = $('#tabaix-seo-img-prompt').val();
        if(!prompt) return alert('Enter a prompt');
        showLoader('Generating image...');
        $('#tabaix-seo-img-preview-area').html('');
        $('#tabaix-seo-img-actions').hide();
        $.post(ajaxUrl, {
            action: 'tabaix_seo_generate_image',
            nonce: nonce,
            post_title: postTitle,
            post_excerpt: prompt,
            style: $('#tabaix-seo-img-style').val(),
            save_to_library: 1
        }, function(res) {
            hideLoader();
            if(res.success) {
                $('#tabaix-seo-img-preview-area').html('<img src="'+res.data.image_url+'" style="width:100%; border-radius:10px">');
                if(res.data.attach_id) {
                    $('#tabaix-seo-btn-set-feat').data('id', res.data.attach_id).parent().css('display', 'flex');
                }
            }
        });
    });

    $('#tabaix-seo-btn-set-feat').on('click', function() {
        var id = $(this).data('id');
        showLoader('Setting featured image...');
        $.post(ajaxUrl, { action: 'tabaix_seo_set_featured_image', nonce: nonce, post_id: postId, attach_id: id }, function(res) {
            hideLoader();
            alert(res.success ? 'Featured image set!' : 'Error: ' + res.data.message);
        });
    });

    // Vision
    $('#tabaix-seo-btn-select-vision').on('click', function(e) {
        e.preventDefault();
        var frame = wp.media({ title: 'Select Image', button: { text: 'Use this image' }, multiple: false });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#tabaix-seo-vision-attach-id').val(attachment.id);
            $('#tabaix-seo-vision-img-preview').html('<img src="'+attachment.url+'" style="width:80px;height:80px;object-fit:cover;border-radius:5px">');
        });
        frame.open();
    });

    $('#tabaix-seo-btn-analyze-vision').on('click', function() {
        var id = $('#tabaix-seo-vision-attach-id').val();
        if(!id) return alert('Select an image');
        showLoader('Analyzing image...');
        $('#tabaix-seo-vision-results').html('');
        $.post(ajaxUrl, { action: 'tabaix_seo_analyze_vision', nonce: nonce, attachment_id: id }, function(res) {
            hideLoader();
            if(res.success) {
                var d = res.data;
                $('#tabaix-seo-vision-results').html('<div style="background:#f8fafc;padding:12px;border-radius:8px;font-size:13px"><p><strong>Title:</strong> <span id="tabaix-seo-vis-title">'+d.title+'</span></p><p><strong>Alt:</strong> <span id="tabaix-seo-vis-alt">'+d.alt+'</span></p><button type="button" class="tabaix-seo-mb-btn tabaix-seo-mb-btn-secondary" id="tabaix-seo-btn-vis-apply">✅ Apply to SEO Meta</button></div>');
                
                $('#tabaix-seo-btn-vis-apply').on('click', function() {
                    $('#tabaix_seo_seo_title').val($('#tabaix-seo-vis-title').text());
                    // Switch to Meta tab to show the change
                    $('.tabaix-seo-mb-tab[data-tab="meta"]').trigger('click');
                });
            }
        });
    });

})(jQuery);
