<?php

add_filter('modify_footer_content', function ($content){

    $pattern = '/<h3 class="sidebar-header">(.*?)<\/h3>/i';
    $replacement = '<h1 class="sidebar-header">$1</h1>';
    $modified_html = preg_replace($pattern, $replacement, $content);

    return $modified_html;
});


add_filter('the_content', 'dom_modify',99);

function dom_modify($content){
    if (empty($content)) return $content;

    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $content); // Suppress warnings for malformed HTML
    $xpath = new DOMXPath($dom);

    // Find all h3 elements that are direct children of an element with the class 'pricing-item-list-content'
    $h3_elements = $xpath->query('//div[contains(@class, "pricing-item-list-content")]/h3');

    foreach ($h3_elements as $h3) {
        // Create a new h1 element
        $h1 = $dom->createElement('h1');

        // Copy attributes from h3 to h1
        foreach ($h3->attributes as $attr) {
            $h1->setAttribute($attr->name, $attr->value);
        }

        // Move child nodes (text, spans, etc.) from h3 to h1
        while ($h3->firstChild) {
            $h1->appendChild($h3->firstChild);
        }

        // Replace the h3 element with the h1 element in the DOM
        $h3->parentNode->replaceChild($h1, $h3);
    }

    $figure_image_links = $xpath->query('//a[contains(@class, "figure-image") and contains(@class, "magnific")]');

    foreach ($figure_image_links as $link) {
        $link->setAttribute('aria-label', 'View full-size image');
    }

    foreach ($xpath->query('//a[@target="_blank"]') as $link) {
        $link->setAttribute('aria-label', 'Opens in a new tab');
    }

    foreach ($xpath->query('//div[contains(@class, "pricing-item-list-content")]/h1') as $h1) {
        $h3 = $dom->createElement('h3');
        while ($h1->firstChild) {
            $h3->appendChild($h1->firstChild);
        }
        $h1->parentNode->replaceChild($h3, $h1);
    }

    $sections = $xpath->query('//div[contains(@class, "background-overlay")]');
    foreach ($sections as $section) {
        $style = $section->getAttribute('style');
        if (strpos($style, 'background-color: rgba(0,0,0,0)') !== false) {
            $section->setAttribute('data-bg__white', 'true');
        }

        if (strpos($style, 'background-color: rgba(0,0,0,0.6)') !== false) {
            $section->setAttribute('data-bg__dark', 'true');
        }
    }

    // Save the modified DOM back to HTML
    $modified_content = '';
    $body_nodes = $dom->getElementsByTagName('body')->item(0)->childNodes;
    foreach ($body_nodes as $node) {
        $modified_content .= $dom->saveHTML($node);
    }

    return $modified_content;
}


add_filter( 'wpcf7_form_elements', 'imp_wpcf7_form_elements' );

function imp_wpcf7_form_elements( $content ) {
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' .$content);
    $xpath = new DOMXPath($dom);
    $elements = $xpath->query('//input | //select | //textarea');
    foreach ($elements as $element) {
        if ($element->hasAttribute('name')) {
            $name = $element->getAttribute('name');
            $element->setAttribute('aria-label', $name);
        }
    }
    $modified_html = $dom->saveHTML();
    return $modified_html;
}

add_filter('cli_show_cookie_bar_only_on_selected_pages', function ($notify_html, $post_slug){
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' .$notify_html);

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//div[@id="cookie-law-info-bar"]');

    foreach ($nodes as $node) {
        $node->setAttribute('role', 'dialog');
        $node->setAttribute('aria-modal', 'true');
    }

    return $dom->saveHTML();
}, 99, 2);