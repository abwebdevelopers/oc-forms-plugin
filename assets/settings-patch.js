$(function () {
    // Find all switches, and activate the label as a toggler
    $('.switch-field .field-switch').each(function () {
        $(this).find('label').attr('for', $(this).parent().find('.custom-switch input').attr('id'));
    });

    // Find all help blocks that have a respective label which supports toggling
    $('.help-block').on('click', function () {
        if ($(this).prev('label[for]').length) {
            $(this).prev('label[for]').click();
        }
    });
});