import { default as Textarea, languages } from '../libs/textarea';

(function ($) {
    'use strict';

    $.fn.wikiEditor = function () {
        return this.each(function () {
            let textarea    = $(this);
            let el          = new Textarea(textarea[0]);
            let toolbar     = $('#wiki-toolbar');
            let select      = $('.select-menu-wrapper', toolbar).find('ul');
            let langMenuOpen = false;

            $.each(languages, function(key, value) {
                select.append('<li><a data-open="```' + key + '<br>" data-close="<br>```" title="Kod źródłowy: ' + value + '">' + value + '</a></li>');
            });

            $('a[data-open], button[data-open]', toolbar).click(function() {
                el.insertAtCaret($(this).data('open').replace(/<br>/g, "\n"), $(this).data('close').replace(/<br>/g, "\n"), el.isSelected() ? el.getSelection() : '');
            });

            $(textarea).bind('keydown', function(e) {
                if ((e.which === 9 || e.keyCode === 9) && e.shiftKey) {
                    el.insertAtCaret("\t", '', "");

                    return false;
                }
            });

            $('.btn-quote', toolbar).click(() => {
                el.insertAtCaret('> ', '', el.getSelection().replace(/\r\n/g, "\n").replace(/\n/g, "\n> "));
            });

            $('.select-menu-search input', toolbar).keyup(function(e) {
                let searchText = $.trim($(this).val().toLowerCase());

                $('li', select).each(function() {
                    $(this).toggle($(this).text().toLowerCase().startsWith(searchText));
                });
            }).on('click mousedown', function(e) {
                e.stopPropagation();
            });

            $('#select-menu', toolbar).on('shown.bs.dropdown', function() {
                setTimeout(function () {
                  $('.select-menu-search input').focus();
                }, 0);
                langMenuOpen = true;
            }).on('hidden.bs.dropdown', function () {
                langMenuOpen = false;
            });

            const langNavigator = (e) => {
                if (langMenuOpen) {
                    switch (e.keyCode) {
                        case 13: {
                          insertActiveLang(e);
                        }break;
                        case 38: {
                          markPrevLangActive();
                        }break;
                        case 40: {
                          markNextLangActive();
                        }break;
                    }
                }
            };

            const langDropdownMenu = toolbar.find('#select-menu .select-menu');
            const langScrollableContent = langDropdownMenu.find('.select-menu-wrapper');

            langDropdownMenu.on('mouseenter', () => {
              unMarkActiveLang();
            });

            const getActiveLangNode = () => {
              return langDropdownMenu.find('a.active');
            };

            const unMarkActiveLang = () => {
              const activeNode = getActiveLangNode();

              if (activeNode) {
                activeNode.removeClass('active');
              }
            };

            const insertActiveLang = (e) => {
              const activeNode = getActiveLangNode();

              if (activeNode) {
                e.preventDefault();
                activeNode.removeClass('active').trigger('click');
              }
            };

            const markNextLangActive = () => {
              const activeNode = getActiveLangNode();

              if (activeNode.length > 0) {
                const nextActiveNode = activeNode.closest('li').next().find('a');

                if (nextActiveNode.length > 0) {
                  nextActiveNode.addClass('active');
                } else {
                  langDropdownMenu.find('a').first().addClass('active');
                }

                activeNode.removeClass('active');
              } else {
                langDropdownMenu.find('a').first().addClass('active');
              }

              scrollToActiveLang();
            };

            const markPrevLangActive = () => {
              const activeNode = getActiveLangNode();

              if (activeNode.length > 0) {
                const prevActiveNode = activeNode.closest('li').prev().find('a');

                if (prevActiveNode.length > 0) {
                  prevActiveNode.addClass('active');
                } else {
                  langDropdownMenu.find('a').last().addClass('active');
                }

                activeNode.removeClass('active');
              } else {
                langDropdownMenu.find('a').last().addClass('active');
              }

              scrollToActiveLang();
            };

            const scrollToActiveLang = () => {
              const activeNode = getActiveLangNode();

              if (activeNode.length > 0) {
                const parentNode = activeNode.closest('li');

                langScrollableContent.scrollTop(parentNode.index() * parentNode[0].offsetHeight);
              }
            };

            $(document).on('keydown', langNavigator);
        });
    };
})(jQuery);
