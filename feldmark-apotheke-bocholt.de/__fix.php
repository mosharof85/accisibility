<?php

add_action('wp_footer', function (){
    ?>
    <script>
        const seenIds = {};
        jQuery(window).on('elementor/frontend/init', function() {

            elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope) {

                // Remove 'aria-label' attribute from elementor-gallery__titles-container divs
                $scope.find('div.elementor-gallery__titles-container').removeAttr('aria-label');

                // Existing swiper-slide-bg aria-label logic
                $scope.find('.swiper-slide-bg').each(function () {
                    var $el = jQuery(this);
                    var bg = window.getComputedStyle(this).getPropertyValue('background-image');

                    if (bg && bg !== 'none') {
                        var match = bg.match(/url\(["']?(.*?)["']?\)/);
                        if (match && match[1]) {
                            var url = match[1];
                            var filename = url.split('/').pop().split('.')[0].replace(/[-_]/g, ' ');
                            $el.attr('aria-label', filename);
                        }
                    }
                });

                // Remove role on gallery titles
                $scope.find('.elementor-item.elementor-gallery-title[role="button"]').each(function () {
                    var $el = jQuery(this);
                    $el.removeAttr('role');
                });

                // Remove redundant gallery titles
                $scope.find('.elementor-item.elementor-gallery-title').remove();

            })

            setTimeout(function (){
                const seenIds = {};
                const elementsWithId = document.querySelectorAll('[id]');
                elementsWithId.forEach(function(el) {
                    const id = el.id;
                    if (seenIds[id]) {
                        const newId = id + '-' + Math.random().toString(36).substr(2, 6);
                        el.id = newId;
                    } else {
                        seenIds[id] = true;
                    }
                    if(el.tagName === 'NAV'){
                        el.setAttribute('aria-label', el.id);
                    }
                });
            },500);

        });

    </script>
    <?php
});

add_filter('elementor/widget/render_content', function ($widget_content, $widget) {
    $widget_name = $widget->get_name();
    if($widget_name == 'image'){
        libxml_use_internal_errors(true);
        $content = '<?xml encoding="UTF-8">' . $widget_content;
        $dom = new DOMDocument();
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        $image = $xpath->query("//img")->item(0);

        $classes = $image->getAttribute('class');

        if (preg_match('/wp-image-(\d+)/', $classes, $matches)) {
            $image_id = (int) $matches[1];
            $current_alt = $image->getAttribute('alt');

            if (empty($current_alt)) {
                // 1. Get custom alt
                $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);

                // 2. Fallback to post title
                if (empty($alt_text)) {
                    $attachment = get_post($image_id);
                    if ($attachment) {
                        $alt_text = $attachment->post_title;
                    }
                }

                // 3. Optional: fallback to filename
                if (empty($alt_text)) {
                    $url = wp_get_attachment_url($image_id);
                    $alt_text = pathinfo($url, PATHINFO_FILENAME);
                }

                if (!empty($alt_text)) {
                    $image->setAttribute('alt', esc_attr($alt_text));
                }
            }
        }

        return $dom->saveHTML();

    }
    return $widget_content;
}, 10, 2);



add_action( 'elementor/frontend/the_content', function ($content){
    libxml_use_internal_errors(true);
    $content = '<?xml encoding="UTF-8">' . $content;
    $dom = new DOMDocument();
    $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    foreach ([
                 '//*[@data-elementor-type="header"]',
                 '//*[@data-elementor-type="footer"]'
             ] as $selector){
        $elementorWrapper = $xpath->query($selector)->item(0);

        if ($elementorWrapper) {
            if($selector ==='//*[@data-elementor-type="footer"]'){
                $main = $dom->createElement('footer');
                $main->appendChild($elementorWrapper->cloneNode(true));
                $elementorWrapper->parentNode->replaceChild($main, $elementorWrapper);
            }

            if($selector ==='//*[@data-elementor-type="header"]'){
                $main = $dom->createElement('header');
                $main->appendChild($elementorWrapper->cloneNode(true));
                $elementorWrapper->parentNode->replaceChild($main, $elementorWrapper);
            }

        }
    }

    return $dom->saveHTML();
}, 99999 );