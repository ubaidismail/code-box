$(".glossary_search input").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            var selectedCharacter = $('.filter_lists li.current').data('letter');

            $(".filter_datas li").filter(function() {
                var textToSearch = $(this).clone().find(".glossary_text").remove().end().text().toLowerCase();
                var match = textToSearch.includes(value.toLowerCase());

                // Check if a character is selected and if the text starts with that character
                if (selectedCharacter && selectedCharacter !== 'all') {
                    match = match && $(this).data('letter') === selectedCharacter;
                }


                // Show all items if no search value and no specific character is selected
                if (value === '' && (!selectedCharacter || selectedCharacter === 'all')) {
                    $(this).addClass('show');
                } else {
                    if (match) {
                        $(this).addClass('show');
                    } else {
                        $(this).removeClass('show');
                    }
                }
            });
        });
