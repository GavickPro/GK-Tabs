/**
 *
 * GK Tabs JS code
 *
 **/
/*

Copyright 2013-2013 GavickPro (info@gavick.com)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/
(function() {
    "use strict";

    jQuery(window).load(function() {
        jQuery(document).find('.gk-tabs').each(function(i, el) {
            el = jQuery(el);
            var animation_speed = el.attr('data-speed') * 1.0;
            var animation_interval = el.attr('data-interval') * 1.0;
            var autoanim = el.attr('data-autoanim');
            var eventActivator = el.attr('data-event');
            var swipe = el.attr('data-swipe');
            var stoponhover = el.attr('data-stoponhover');
            var active_tab = el.attr('data-active') * 1.0;
            var hoverstate = false;
            var tabs = el.find('.gk-tabs-item');
            var items = el.find('.gk-tabs-nav li');
            var tabs_wrapper = jQuery(el.find('.gk-tabs-container')[0]);
            var current_tab = active_tab;
            var previous_tab = null;
            var amount = tabs.length;
            var blank = false;
            var falsy_click = false;
            var tabs_h = [];

            jQuery(tabs).each(function(i, item) {
                tabs_h[i] = jQuery(item).outerHeight();
            });

            // add events to tabs
            items.each(function(i, item) {
                item = jQuery(item);
                item.bind(eventActivator, function() {
                    if (i !== current_tab) {
                        previous_tab = current_tab;
                        current_tab = i;

                        if (typeof gk_tab_event_trigger !== 'undefined') {
                            gk_tab_event_trigger(current_tab, previous_tab, el.parent().parent().attr('id'));
                        }

                        tabs_wrapper.css('height', tabs_wrapper.outerHeight() + 'px');
                        //
                        setTimeout(function() {
                            jQuery(tabs[previous_tab]).css({
                                'position': 'absolute',
                                'top': '0',
                                'z-index': '1'
                            });

                            jQuery(tabs[current_tab]).css({
                                'position': 'relative',
                                'z-index': '2'
                            });

                            jQuery(tabs[previous_tab]).removeClass('active');
                            jQuery(tabs[current_tab]).addClass('active');

                            tabs_wrapper.animate({
                                    "height": tabs_h[i]
                                },
                                animation_speed / 2,
                                function() {
                                    tabs_wrapper.css('height', 'auto');
                                }
                            );
                        }, animation_speed / 2);
                        // common operations for both types of animation
                        if (!falsy_click) {
                            blank = true;
                        } else {
                            falsy_click = false;
                        }
                        jQuery(items[previous_tab]).removeClass('active');
                        jQuery(items[current_tab]).addClass('active');
                    }
                });
            });
            // stop on hover
            if (stoponhover === 'on') {
                tabs_wrapper.mouseenter(function() {
                    hoverstate = true;
                });

                tabs_wrapper.mouseleave(function() {
                    hoverstate = false;
                });
            }
            // auto-animation
            if (autoanim === 'on') {
                setInterval(function() {
                    if (!blank && !hoverstate) {
                        falsy_click = true;
                        var next = current_tab < amount - 1 ? current_tab + 1 : 0;
                        jQuery(items[next]).trigger(eventActivator);
                    } else {
                        blank = false;
                    }
                }, animation_interval);
            }
            // navigation buttons
            if (el.find('.gk-tabs-prev').length) {
                el.find('.gk-tabs-prev').click(function() {
                    var next = current_tab > 0 ? current_tab - 1 : items.length - 1;
                    jQuery(items[next]).trigger(eventActivator);
                    blank = true;
                });

                el.find('.gk-tabs-next').click(function() {
                    var next = current_tab < amount - 1 ? current_tab + 1 : 0;
                    jQuery(items[next]).trigger(eventActivator);
                    blank = true;
                });
            }
            // swipe gesture support
            if (swipe === 'on') {
                var pos_start_x = 0;
                var pos_start_y = 0;
                var time_start = 0;
                var swipe_state = false;

                tabs_wrapper.bind('touchstart', function(e) {
                    swipe_state = true;
                    var touches = e.originalEvent.changedTouches || e.originalEvent.touches;

                    if (touches.length > 0) {
                        pos_start_x = touches[0].pageX;
                        pos_start_y = touches[0].pageY;
                        time_start = new Date().getTime();
                    }
                });

                tabs_wrapper.bind('touchmove', function(e) {
                    var touches = e.originalEvent.changedTouches || e.originalEvent.touches;

                    if (touches.length > 0 && swipe_state) {
                        if (
                            Math.abs(touches[0].pageX - pos_start_x) > Math.abs(touches[0].pageY - pos_start_y)
                        ) {
                            e.preventDefault();
                        } else {
                            swipe_state = false;
                        }
                    }
                });

                tabs_wrapper.bind('touchend', function(e) {
                    var touches = e.originalEvent.changedTouches || e.originalEvent.touches;

                    if (touches.length > 0 && swipe_state) {
                        if (
                            Math.abs(touches[0].pageX - pos_start_x) >= 100 &&
                            new Date().getTime() - time_start <= 500
                        ) {
                            if (touches[0].pageX - pos_start_x > 0) {
                                var next = current_tab > 0 ? current_tab - 1 : items.length - 1;
                                jQuery(items[next]).trigger(eventActivator);
                                blank = true;
                            } else {
                                var nexttab = current_tab < amount - 1 ? current_tab + 1 : 0;
                                jQuery(items[nexttab]).trigger(eventActivator);
                                blank = true;
                            }
                        }
                    }
                });
            }
        });
    });
})();
