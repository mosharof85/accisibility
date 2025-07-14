<?php

add_action('wp_head', function (){
    ?>
    <style>
        .ekit-toggle-span{
            cursor: pointer;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observerConfig = { childList: true, subtree: true };
            const processedClass = 'doctor-box-processed';

            function processDoctorBox(doctorBoxElement) {
                if (doctorBoxElement.classList.contains(processedClass)) {
                    return;
                }

                const titleElement = doctorBoxElement.querySelector('.doctor_box_title');
                const roleElement = doctorBoxElement.querySelector('.doctor_box_role');
                const allLinksInBox = doctorBoxElement.querySelectorAll('a');

                let ariaLabelBase = '';

                if (titleElement && roleElement) {
                    const title = titleElement.textContent.trim();
                    const role = roleElement.textContent.trim();
                    ariaLabelBase = `Profil von ${title}, ${role} anzeigen`;
                }

                allLinksInBox.forEach(link => {
                    if (link.classList.contains('doctor_box_link')) {
                        if (ariaLabelBase) {
                            link.setAttribute('aria-label', ariaLabelBase);
                        }
                    } else {
                        link.remove();
                    }
                });

                doctorBoxElement.classList.add(processedClass);
            }

            const observerCallback = function(mutationsList, observer) {
                for (const mutation of mutationsList) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        for (const node of mutation.addedNodes) {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                if (node.classList.contains('doctor_box')) {
                                    processDoctorBox(node);
                                }
                                const descendantDoctorBoxes = node.querySelectorAll('.doctor_box');
                                descendantDoctorBoxes.forEach(processDoctorBox);
                            }
                        }
                    }
                }
            };

            const observer = new MutationObserver(observerCallback);

            observer.observe(document.body, observerConfig);

            const existingDoctorBoxes = document.querySelectorAll('.doctor_box');
            existingDoctorBoxes.forEach(processDoctorBox);
        });
    </script>
    <?php
});

add_action('wp_footer', function (){
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('.page_banner_title').each(function() {
                var $this = $(this);
                var newElement = $('<h2/>').html($this.html());
                $.each(this.attributes, function() {
                    if (this.name !== 'class') {
                        newElement.attr(this.name, this.value);
                    }
                });
                newElement.addClass($this.attr('class'));
                $this.replaceWith(newElement);
            });
        });
    </script>
    <script>
        const seenIds = {};
        jQuery(window).on('elementor/frontend/init', function() {

            elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope) {

                // Remove 'aria-label' attribute from elementor-gallery__titles-container divs
                $scope.find('div.elementor-gallery__titles-container').removeAttr('aria-label');

                // Existing swiper-slide-bg aria-label logic
                $scope.find('.swiper-slide-bg').each(function() {
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
                $scope.find('.elementor-item.elementor-gallery-title[role="button"]').each(function() {
                    var $el = jQuery(this);
                    $el.removeAttr('role');
                });

                // Add alt to empty images
                setTimeout(function (){
                    $scope.find("img[alt='']").each(function() {
                        this.setAttribute('alt', window.location.origin);
                    });
                },500);

                // Remove redundant gallery titles
                $scope.find('.elementor-item.elementor-gallery-title').remove();

                // ✅ YOUR a[data-ekit-toggle] → span replacement
                $scope.find('a[data-ekit-toggle]').each(function() {
                    var $element = jQuery(this);
                    var $span = jQuery('<span>');

                    // Copy class and add 'ekit-toggle-span'
                    var existingClasses = $element.attr('class') || '';
                    $span.addClass(existingClasses).addClass('ekit-toggle-span');

                    // Copy other attributes
                    jQuery.each(this.attributes, function() {
                        if (this.name !== 'class') {
                            $span.attr(this.name, this.value);
                        }
                    });

                    // Move child nodes
                    $span.append($element.contents());

                    // Replace <a> with <span>
                    $element.replaceWith($span);
                });

                // Set aria-label from data-elementor-lightbox-title if missing on gallery links
                $scope.find('a.e-gallery-item.elementor-gallery-item').each(function() {
                    var $el = jQuery(this);
                    if (!$el.attr('aria-label') || $el.attr('aria-label').trim() === '') {
                        var title = $el.attr('data-elementor-lightbox-title');
                        if (title) {
                            $el.attr('aria-label', title);
                        }
                    }
                });

                // Set aria-label from data-thumbnail filename if missing on gallery image containers
                $scope.find('div.e-gallery-image.elementor-gallery-item__image').each(function() {
                    var $el = jQuery(this);
                    if (!$el.attr('aria-label') || $el.attr('aria-label').trim() === '') {
                        var thumbnail = $el.attr('data-thumbnail');
                        if (thumbnail) {
                            var path = new URL(thumbnail, window.location.origin).pathname;
                            var filename = path.substring(path.lastIndexOf('/') + 1).split('.')[0];
                            var label = filename.replace(/[-_]/g, ' ');
                            $el.attr('aria-label', label);
                        }
                    }
                });

                $scope.find('div.swiper-slide-bg').each(function() {
                    var $el = jQuery(this);
                    var style = $el.attr('style') || '';
                    var match = style.match(/background-image:\s*url\(["']?(.*?)["']?\)/i);
                    if (match && match[1]) {
                        var path = new URL(match[1], window.location.origin).pathname;
                        var filename = path.substring(path.lastIndexOf('/') + 1).split('.')[0];
                        $el.attr('aria-label', filename);
                    }
                });

            });



            // Global adjustments outside widget scope
            jQuery('.ekit-template-content-markup.ekit-template-content-header').attr('role', 'banner');
            jQuery('.ekit-template-content-markup.ekit-template-content-footer').attr('role', 'contentinfo');
            jQuery('div[role="main"]').attr('id', 'content');

            // Fix duplicate IDs and add ARIA labels for NAV elements
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

add_action('elementskit/template/before_header', function (){
    ?>
    <h1 class="sr-only"><?php echo esc_html( wp_get_document_title()); ?>//</h1>
    <?php
});

//add_filter( 'elementskit_enable_skip_link', '__return_false' );