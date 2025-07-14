<?php

add_action('wp_head', function (){
    ?>

    <style>
        :root{
            --newColor: #1B7D00
        }

        a.nectar-button.regular.regular-button {
            color: #fff;
            background-color: var(--newColor) !important;
        }

        #footer-outer #copyright a:not(.nectar-button){
            background-color: #FFFFFF;
        }

        #slide-out-widget-area,
        #BorlabsCookieBox ._brlbs-btn-accept-all{
            background-color: var(--newColor) !important;
            background: var(--newColor) !important;
        }

        _brlbs-btn._brlbs-btn-accept-all._brlbs-cursor{
            color: #ffffff;
        }
        #BorlabsCookieBox ._brlbs-refuse a,
        #BorlabsCookieBox ._brlbs-legal a{
            color: #000000;
        }

        .h4_font{
            font-weight: 600;
        }

        .article-content-wrap a{
            color: var(--newColor) !important;
        }
        .article-content-wrap a em,
        #sidebar a,
        #BorlabsCookieBox a,
        .h4_font a{
            color: var(--newColor) ;
        }

        #slide-out-widget-area li a{
            color: #FFFFFF;
        }
        #header-outer[data-lhe="animated_underline"] li > a .menu-title-text{
            display: inline-block;
            color: #000000;
            background-color: #FFFFFF;
        }

        .sidebar_widget_title{
            font-weight: bold;
            color: #000000;
            font-size: 16px;
        }

        .post-meta .month,
        .post-meta .day{
            display: inline-block !important;
            color: #282828;
        }
    </style>
    <script>
        jQuery(document).ready(function($) {
            $(window).on('load', function() {
                $('.owl-carousel button.owl-dot').each(function() {
                    $(this).attr('aria-label', 'Karussell-Navigationsschaltfläche');
                });

                $('.nectar_icon svg').each(function (){
                    $(this).attr('role', 'img');
                    $(this).attr('aria-hidden', 'true');
                    $(this).attr('focusable', 'false');
                })

                $('a.nectar-button span').each(function() {
                    $(this).replaceWith($(this).text());
                });

                $('.swiper-container .slider-down-arrow').remove();

                $('a[href]').each(function() {
                    const $link = $(this);
                    if ($link.text().trim() === '' && $link.find('img, svg').length === 0) {
                        const href = $link.attr('href');
                        const match = href.match(/\/([^\/]+)\/?$/);

                        if (match && match[1]) {
                            let segment = match[1];
                            segment = segment.replace(/-/g, ' ');
                            let ariaLabel = segment.charAt(0).toUpperCase() + segment.slice(1);
                            $link.attr('aria-label', ariaLabel);
                        }
                    }
                });

                $('.carousel-item h4').each(function() {
                    const $h4 = $(this);
                    const $p = $('<p>');
                    $.each(this.attributes, function() {
                        if (this.specified) {
                            $p.attr(this.name, this.value);
                        }
                    });
                    $p.addClass('h4_font');
                    $p.html($h4.html());
                    $h4.replaceWith($p);
                });

                $('form[role="search"]').each(function (){
                    const $searchForm = $(this)
                    const $existingSubmitButton = $searchForm.find('button[type="submit"], input[type="submit"]');
                    if ($existingSubmitButton.length === 0) {
                        const hiddenSubmitButtonHTML = '<button type="submit" class="screen-reader-text">Formular senden</button>';
                        $searchForm.append(hiddenSubmitButtonHTML);
                    }
                });

                $('.leaflet-marker-icon').attr('aria-label', 'Standortmarkierung')
                $('img.leaflet-tile').each(function() {
                    $(this).attr('alt', 'Google-Kartenausschnitt');
                });

                $('img[alt=""]').each(function() {
                    const $img = $(this); // Get the current image element as a jQuery object
                    const imageUrl = $img.attr('src'); // Get the image source URL

                    if (imageUrl) {
                        let filename = imageUrl.substring(imageUrl.lastIndexOf('/') + 1);
                        filename = filename.substring(0, filename.lastIndexOf('.'));
                        filename = filename.replace(/-\d+x\d+$/, '');
                        filename = filename.replace(/[-_]/g, ' ');

                        let altText = filename.replace(/\b\w/g, char => char.toUpperCase());
                        if (altText.trim() !== '') {
                            $img.attr('alt', altText.trim());
                            if ($img.attr('title')) {
                                $img.removeAttr('title');
                            }
                        }
                    }
                });

                $('#single-meta .meta-comment-count a').css(
                    {
                        'color': 'var(--newColor) !important',
                        'border-color': 'var(--newColor) !important'
                    }
                )

                $('p:has(strong)').each(function() {
                    $(this).find('strong').contents().unwrap();
                    $(this).css('font-weight', 'bold');
                });
            });
        });
    </script>
    <?php
});

add_filter('dynamic_sidebar_params', function ($params){
    if (isset($params[0]['before_title'])) {
        $params[0]['before_title'] = str_replace(
            array('<h1', '<h2', '<h3', '<h4', '<h5', '<h6'),
            '<p class="sidebar_widget_title"',
            $params[0]['before_title']
        );

        $params[0]['after_title'] = str_replace(
            array('</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'),
            '</p>',
            $params[0]['after_title']
        );
    }

    return $params;

});

add_filter( 'get_search_form', function ($form){
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $form, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $input_field = $xpath->query('//input[@name="s"]')->item(0);
    $input_field->setAttribute('aria-label', 'Suchbegriff eingeben');

    $submit_button = $xpath->query('//input[@type="submit"] | //button[@type="submit"]')->item(0);
    $submit_button->setAttribute('aria-label', 'Suche starten');

    $span_in_submit_button = $xpath->query('//button[@type="submit"]//span[@class="text"]')->item(0);
    $span_in_submit_button->parentNode->removeChild($span_in_submit_button);

    $modified_form = $dom->saveHTML($dom->documentElement);
    $modified_form = str_replace('<?xml encoding="UTF-8">', '', $modified_form);
    return $modified_form;
});