<?php

add_action('wp_head', function (){
    ?>
    <script>
        window.addEventListener('DOMContentLoaded', function (){
            (function ($){
                $('iframe').each(function (){
                    $(this).attr('title', 'Embeded Content')
                })
            })(jQuery);
        })
    </script>
    <?php
});

add_action('wp_footer', function (){
   ?>
    <script>
        const seenIds = {};
        jQuery(function($) {
            $(window).on('elementor/frontend/init', function() {
                elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope) {
                    $scope.find('h1.uael-divider-text.elementor-inline-editing, \
                   h2.uael-divider-text.elementor-inline-editing, \
                   h3.uael-divider-text.elementor-inline-editing, \
                   h4.uael-divider-text.elementor-inline-editing, \
                   h5.uael-divider-text.elementor-inline-editing, \
                   h6.uael-divider-text.elementor-inline-editing').each(function() {
                        var $this = $(this);
                        var newSpan = $('<span>', {
                            html: $this.html(),
                            class: $this.attr('class'),
                            id: $this.attr('id')
                        });
                        $this.replaceWith(newSpan);
                    });

                    $scope.find('iframe').attr('title','Embed Content')
                    $scope.find('.elementor-image-carousel-wrapper[role="region"]').removeAttr('role')

                    // $scope.find('.elementor-icon-box-title span').each(function() {
                    //     $(this).contents().unwrap();
                    // });

                    // New code for Text Editor widget
                    if ($scope.data('widget_type') === 'text-editor.default') {
                        $scope.find('p, h4, h5').each(function() {
                            var $el = $(this);
                            var $strong = $el.children('strong');
                            if ($el.contents().length === 1 && $strong.length === 1) {
                                $strong.contents().unwrap();
                                $el.css('font-weight', 'bold');
                            }
                        });

                        $scope.find('h4, h5').each(function() {
                            var $heading = $(this);
                            var $p = $('<p>');
                            $.each(this.attributes, function() {
                                $p.attr(this.name, this.value);
                            });
                            if ($heading.is('h4')) {
                                $p.addClass('h4_font');
                            }
                            $p.append($heading.contents());
                            $heading.replaceWith($p);
                        });

                        $scope.find('li > p').each(function() {
                            $(this).contents().unwrap();
                        });
                    }
                });

                (function() {
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
                })();

                (function (){
                    let $skipLink = $('a.skip-link.screen-reader-text');
                    if ($skipLink.length) {
                        $('body').prepend($skipLink);
                    }
                    $('.ui-datepicker-calendar').prepend(`<caption>Calender</caption>`)

                    // $('div[aria-label]').removeAttr('aria-label')
                })()
            });
        });
    </script>
    <?php
});

add_filter('elementor/widget/render_content', function ($widget_content, $widget) {
    $widget_name = $widget->get_name();
    if(in_array($widget_name, ['icon', 'icon-box', 'button', 'uael-nav-menu', 'contact-buttons-var-10', 'form', 'image'])){
        libxml_use_internal_errors(true);
        $content = mb_convert_encoding($widget_content, 'HTML-ENTITIES', 'UTF-8');
        $dom = new DOMDocument();
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $settings = $widget->get_settings();
        if (!empty($settings['_attributes'])){
            $attributes = preg_split('/\r\n|\r|\n/', $settings['_attributes']);
            foreach ($attributes as $attribute) {
                $parts = explode('|', $attribute);
                if (count($parts) === 2) {
                    $attr_name = trim($parts[0]);
                    $attr_value = trim($parts[1]);
                    if ($attr_name !== '' && $attr_value !== '') {
                        if($widget_name === 'uael-nav-menu'){
                            $nav = $xpath->query('//nav')->item(0);
                            if ($nav instanceof DOMElement) {
                                $nav->setAttribute($attr_name, $attr_value);
                            }
                        }
                        if($widget_name === 'icon-box'){
                            $a_tag = $xpath->query('//a')->item(0);
                            if ($a_tag instanceof DOMElement) {
                                $a_tag->setAttribute($attr_name, $attr_value);
                            }
                        }
                    }
                }
            }
        }

        if (in_array($widget_name, ['icon', 'button'])) {
            if (isset($settings['__dynamic__']['link'])) {
                $link = $settings['__dynamic__']['link'];
                if (preg_match('/settings="([^"]+)"/', $link, $matches)) {
                    $encodedSettings = $matches[1];
                    $decodedSettings = urldecode($encodedSettings);
                    $settingsArray = json_decode($decodedSettings, true);
                    if (isset($settingsArray['popup'])) {
                        $popupId = $settingsArray['popup'];
                        $popup_post = get_post($popupId);
                        if ($popup_post && $popup_post->post_type === 'elementor_library') {
                            $popup_title = $popup_post->post_title;
                            $a_tag = $xpath->query('//a')->item(0);
                            if ($a_tag instanceof DOMElement) {
                                $visibleText = trim($a_tag->textContent);
                                if(!empty($visibleText)){
                                    $a_tag->setAttribute('aria-label', $visibleText);
                                }
                                else{
                                    $a_tag->setAttribute('aria-label', "Open " . $popup_title);
                                }
                            }
                        }
                    }
                }
            }
        }

        if($widget_name === 'form') {
            $labels = $xpath->query('//label[@for]');

            foreach ($labels as $label) {
                $forId = $label->getAttribute('for');
                $input = $xpath->query("//*[@id='$forId']")->item(0);

                if ($input) {
                    // Ensure the label has an 'id' attribute
                    if (!$label->hasAttribute('id')) {
                        $labelId = $forId . '-label';
                        $label->setAttribute('id', $labelId);
                    } else {
                        $labelId = $label->getAttribute('id');
                    }

                    // Set 'aria-labelledby' on the input element
                    if (!$input->hasAttribute('aria-labelledby')) {
                        $input->setAttribute('aria-labelledby', $labelId);
                    }

                    if ($input->nodeName === 'select') {
                        $label->parentNode->removeChild($label);
                        $input->parentNode->insertBefore($label, $input);
                    }
                }
            }
        }

//        if($widget_name === 'image'){
//            if (strpos($settings['_attributes'], 'data-title') !== false) {
//                write_log($widget_content);
//            }
//        }

        return $dom->saveHTML();

    }
    return $widget_content;
}, 10, 2);



add_filter('elementor/frontend/the_content', function ($content) {
    libxml_use_internal_errors(true);
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');

    $dom = new DOMDocument();
    $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);


    $header_template = $xpath->query('//div[contains(@class, "elementor-location-header")]')->item(0);

    if ($header_template) {
        $header_template->setAttribute('role', 'banner');
//        $skip_link = $dom->createElement('a', 'Zum Inhalt springen');
//        $skip_link->setAttribute('href', '#main');
//        $skip_link->setAttribute('title', 'Zum Inhalt springen');
//        $skip_link->setAttribute('class', 'skip-link screen-reader-text');
//        $header_template->insertBefore($skip_link, $header_template->firstChild);
    }

    $floating_buttons = $xpath->query('//div[contains(@class, "elementor-location-floating_buttons")]')->item(0);

    if ($floating_buttons) {
        $floating_buttons->setAttribute('role', 'dialog');
        $floating_buttons->setAttribute('aria-label', 'Kontakt-Schaltflächen');

        $xpath->query("//div[@aria-role='dialog']")->item(0)->removeAttribute('aria-role');

        $anchorNodes = $xpath->query(".//a[contains(concat(' ', normalize-space(@class), ' '), ' e-contact-buttons__contact-icon-link ')]", $floating_buttons);

        foreach ($anchorNodes as $anchor) {
            $span = $xpath->query(".//span[contains(@class, 'e-contact-buttons__contact-title')]", $anchor)->item(0);
            if ($span) {
                $uniqueId = 'label-' . uniqid();
                $span->setAttribute('id', $uniqueId);
                $anchor->setAttribute('aria-labelledby', $uniqueId);
                if ($anchor->hasAttribute('aria-label')) {
                    $anchor->removeAttribute('aria-label');
                }
            }
        }
    }

    $loop_items = $xpath->query(('//div[contains(@class, "e-loop-item")]'));

    foreach ($loop_items as $item){
        $className = $item->getAttribute('class');
        if (preg_match('/\bpost-(\d+)\b/', $className, $matches)) {
            $post_id = (int) $matches[1];
            $post_title = get_the_title($post_id);
            $caption = $xpath->query(".//figcaption", $item)->item(0);
            $img = $xpath->query(".//img", $item)->item(0);
            if($caption && !empty($caption->textContent)){
                $img->setAttribute('alt', $caption->textContent);
            }
            else{
                $img->setAttribute('alt', $post_title);
            }
        }
    }

    return $dom->saveHTML();
}, 99999);


function post_title_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => get_the_ID(),
    ), $atts, 'post_title' );

    $post_title = get_the_title( $atts['id'] );
    return $post_title;
}
add_shortcode( 'post_title', 'post_title_shortcode' );