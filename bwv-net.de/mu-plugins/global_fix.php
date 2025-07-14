<?php

add_filter( 'doing_it_wrong_trigger_error', '__return_false' );

$GLOBALS['nav_unique_ids'] = [];

function check_hTag($input_string) {
    $pattern = '/^h\d$/';
    return preg_match($pattern, $input_string) === 1;
}

function replace_h_tag ($element, $dom){
    if(check_hTag($element->tagName)){
        $p_element = $dom->createElement('p');
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attr) {
                $p_element->setAttribute($attr->nodeName, $attr->nodeValue);
            }
        }
        while ($element->hasChildNodes()) {
            $child = $element->firstChild;
            $element->removeChild($child);
            $p_element->appendChild($child);
        }
        if ($element->parentNode) {
            $element->parentNode->replaceChild($p_element, $element);
        }
    }
}

add_filter( 'elementor/widget/render_content', function ($widget_content, $widget){

    $isScript = false;
    if(str_contains($widget_content, '<script')){
        $isScript = true;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $widget_content);
    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//nav') as $nav) {
        $current_id = $nav->getAttribute('id');
        if(!empty($current_id)){
            $new_id = $current_id . '-' . uniqid();
        }
        else{
            $new_id = uniqid();
        }
        $nav->setAttribute('id', $new_id);
    }

    foreach ([
                 '//*[contains(@class, "elementor-icon-box-title")]',
                 '//*[contains(@class, "elementor-post__title")]',
                 '//*[contains(@class, "elementor-heading-title")]',
                 '//*[contains(@class, "teaser_leistungen")]/*',
                 '//*[contains(@class, "teaser_link")]/*',

             ] as $selector){
        foreach ($xpath->query($selector) as $hTag) {
            replace_h_tag($hTag, $dom);
        }
    }

    foreach ($xpath->query('//img') as $img_tag) {
        if(!$img_tag->hasAttribute('alt') || empty($img_tag->getAttribute('alt'))){
            $src = $img_tag->getAttribute('src');
            if ($src) {
                $filename = pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_FILENAME);
                $alt_text = ucwords(str_replace(array('-', '_'), ' ', $filename));
                $img_tag->setAttribute("alt", $alt_text);
            } else {
                $img_tag->setAttribute("alt", "Image");
            }
        }
    }

    foreach ($xpath->query('//button[contains(@class, "exad-search-submit")]') as $button) {
        $button->setAttribute("aria-label", "Submit Search Form Button");
    }

    foreach ($xpath->query('//div[contains(@class, "exad-search-form-container")]') as $search_container) {
        $search_container->removeAttribute('role');
    }

    foreach ($xpath->query('//input') as $input) {
        $title_value = $input->getAttribute('title');
        if (!empty($title_value) && !$input->hasAttribute('aria-label')) {
            $input->setAttribute('aria-label', $title_value);
        }
    }

    foreach ($xpath->query('//a[contains(@class, "elementor-post__read-more")]') as $link) {
        $aria_label = $link->getAttribute('aria-label');
        if ($aria_label) {
            $unique_id = uniqid('readmore-');
            $link->setAttribute('id', $unique_id);
            $link->removeAttribute('aria-label');
            $context_id = $unique_id . '-context';
            $link->setAttribute('aria-labelledby', $unique_id . ' ' . $context_id);
            $span = $dom->createElement('span', $aria_label);
            $span->setAttribute('id', $context_id);
            $span->setAttribute('class', 'screen-reader-text');
            if ($link->parentNode) {
                $link->parentNode->insertBefore($span, $link->nextSibling);
            }
        }
    }

    foreach ($xpath->query('//*[@role="img"]') as $element) {
        $element->setAttribute('aria-hidden', 'true');
    }

    foreach ($xpath->query('//a[contains(@href, "twitter.com")]') as $link) {
        $class = $link->getAttribute('class');
        $class = preg_replace('/\belementor-social-icon-[^\s]*/', '', $class);
        $class .= ' elementor-social-icon-twitter';
        $class = trim(preg_replace('/\s+/', ' ', $class));
        $link->setAttribute('class', trim($class));
        foreach ($xpath->query('.//span[contains(@class, "elementor-screen-only")]', $link) as $span) {
            $span->nodeValue = 'Twitter';
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    $new_content = '';
    foreach ( $body->childNodes as $child ) {
        $new_content .= $dom->saveHTML( $child );
    }

    if($isScript){
        return $widget_content;
    }

    return $new_content;

}, 9999, 2 );


add_action( 'elementor/frontend/the_content', function ($content){
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
    $xpath = new DOMXPath($dom);

    foreach ([
                 '//*[@data-elementor-type="wp-page"]',
                 '//*[@data-elementor-type="archive"]',
                 '//*[@data-elementor-type="footer"]'
             ] as $selector){
        $elementorWrapper = $xpath->query($selector)->item(0);

        if ($elementorWrapper) {
            if($selector ==='//*[@data-elementor-type="footer"]'){
                $main = $dom->createElement('footer');
            }
            else{
                $main = $dom->createElement('main');
                $main->setAttribute('id', 'primary');

                $a_tag = $dom->createElement('a');
                $a_tag->appendChild($dom->createTextNode('Skip to content'));
                $a_tag->setAttribute('class', 'skip-link screen-reader-text');
                $a_tag->setAttribute('href', '#primary');

                $h1_tag = $dom->createElement('h1');
                $h1_tag->appendChild($dom->createTextNode(esc_html( wp_get_document_title())));
                $h1_tag->setAttribute('class', 'sr-only');

//                $main->appendChild($a_tag);
                $main->appendChild($h1_tag);
            }
            $main->appendChild($elementorWrapper->cloneNode(true));
            $elementorWrapper->parentNode->replaceChild($main, $elementorWrapper);
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    $new_content = '';
    if ($body) {
        foreach ($body->childNodes as $child) {
            $new_content .= $dom->saveHTML($child);
        }
    }

    return $new_content;
}, 99999 );

add_action( 'wp_head', function() {
    ?>
    <style>
        .teaser_link{
            display: flex !important;
            align-items: center;
            justify-content: space-between;
        }

        .teaser_link .image_icon img{
            margin-top: -15px;
        }

        .teaser_link p,
        .teaser_leistungen p{
            margin: 0;
            font-weight: bold;
        }
        p.elementor-icon-box-title{
            margin: 0;
        }
    </style>

    <script>
        window.addEventListener('DOMContentLoaded', function (){
            let checkNavUnderBodyTag = setInterval(function (){
                if(document.querySelector('body > nav')){
                    clearInterval(checkNavUnderBodyTag)
                    document.querySelector('body > nav').insertAdjacentHTML('beforebegin', `
                        <a class="skip-link screen-reader-text" href="#primary">Skip to content</a>
                    `)
                }
            },10)
        })

        let checkInstaGram = setInterval(function (){
            let instagramIframe = document.querySelector('#instagram-embed-0');
            if(instagramIframe){
                clearInterval(checkInstaGram);
                instagramIframe.focus()
                let checkInstagramImages = setInterval(function (){
                    if(document.querySelector('.HoverCardPhotos')){
                        clearInterval(checkInstagramImages)
                        console.log(document.querySelector('.HoverCardPhotos'))
                    }
                },10)
            }
        },10)
    </script>
    <?php
} );

add_action('wp_footer', function (){
    ?>
    <script>
        const seenIds = {};
        jQuery(window).on('elementor/frontend/init', function() {
            setTimeout(function (){
                const elementsWithId = document.querySelectorAll('[id]');
                // const duplicateIds = [];

                elementsWithId.forEach(function(el) {
                    const id = el.id;
                    if (seenIds[id]) {
                        const newId = id + '-' + Math.random().toString(36).substr(2, 6);
                        el.id = newId;
                        // duplicateIds.push({ original: id, new: newId });
                    } else {
                        seenIds[id] = true;
                    }
                    if(el.tagName === 'NAV'){
                        el.setAttribute('aria-label', el.id);
                    }
                });

                // document.querySelectorAll('nav:not([id])').forEach(navWithOutId=>{
                //     let settings = JSON.parse(navWithOutId.getAttribute('data-settings'));
                //     if(settings){
                //         const newId =  `pp-menu-${settings.menu_id}`+'-'+Math.random().toString(36).substr(2, 6);
                //         navWithOutId.id = newId;
                //     }
                // })
            },500)

            document.querySelectorAll('iframe').forEach(iframe=>{
                iframe.setAttribute('title','Embed Content')
            })
            //
            // let checkImgWithoutAltTag = setInterval(function (){
            //     if(document.querySelector('img').src.includes('cdninstagram')){
            //         clearInterval(checkImgWithoutAltTag);
            //         console.log('Found')
            //         document.querySelectorAll('img').forEach(img=>{
            //             if(!img.hasAttribute('alt')){
            //                 img.setAttribute('alt', '')
            //                 img.setAttribute('role', 'presentation')
            //             }
            //         });
            //     }
            // },100)
        });
    </script>
    <?php
});

/***
 * Color Contrast Issues
 */

add_action('wp_head', function (){
    ?>
    <style>

        body.page-id-10158{
            --e-global-color-primary: #0f7f2c;
            --e-global-color-accent: #0c6c26;
        }

        body.page-id-10158 .elementor-element-f8f13b0 polygon{
            fill: #0f7f2c;
        }

        body.page-id-10158 .elementor-element-f8f13b0 polygon:hover{
            fill: #0c6c26;
        }

        body.page-id-10158 .elementor-posts .elementor-post__meta-data{
            color: #6B6B6B !important;
        }

        body.page-id-10158 .teaser_link,
        body.page-id-10158 .teaser_leistungen{
            background: #006400CC;
        }

        body.page-id-10158 .teaser_link p,
        body.page-id-10158 .teaser_leistungen p{
            color:#ffffff;
        }

        body.page-id-10158 .elementor-element-ea09022 a
        body.page-id-10158 .elementor-element-17f0e51 a{
            background-color: var(--e-global-color-accent) !important;
            color: #ffffff !important;
        }
        .elementor-button.elementor-slide-button,
        .elementor-slide-heading,
        .elementor-slide-description{
            color: #fff;
        }

        #CookieBoxTextHeadline,
        span._brlbs-paragraph,
        p._brlbs-service-group-description{
            color: #555;
        }
    </style>
    <?php
});

add_action('upgrader_process_complete', 'my_plugin_post_update_override', 10, 2);

function my_plugin_post_update_override($upgrader_object, $options) {
    $plugin_slug = 'powerpack-elements/powerpack-elements.php'; // The main plugin file path
    $modified_file_path = WP_CONTENT_DIR . '/mu-plugins/powerpack-elements-mod/advanced-menu.php'; // Your custom file location
    $target_plugin_file_path = WP_PLUGIN_DIR . '/powerpack-elements/modules/advanced-menu/widgets/advanced-menu.php'; // The file to be replaced

    if (isset($options['action']) && $options['action'] == 'update' && isset($options['type']) && $options['type'] == 'plugin') {
        if (isset($options['plugins']) && in_array($plugin_slug, $options['plugins'])) {
            // Check if your custom file exists and the target plugin file exists
            if (file_exists($modified_file_path) && file_exists($target_plugin_file_path)) {
                // Perform the file copy
                $copy_success = copy($modified_file_path, $target_plugin_file_path);
                if ($copy_success) {
                    error_log("Successfully re-applied custom file for {$plugin_slug}");
                } else {
                    error_log("Failed to re-apply custom file for {$plugin_slug}");
                }
            }
        }
    }
}