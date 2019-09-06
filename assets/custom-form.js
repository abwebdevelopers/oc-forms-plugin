var checkForShittyJqueryAttempt = 0;
var checkForShittyJquery = window.setInterval(function () {
  if ($ !== undefined) {
    clearInterval(checkForShittyJquery);

    bootstrapAbwebForm($);
  }
  checkForShittyJqueryAttempt++;

  if (checkForShittyJqueryAttempt > (20 * 15)) { // 15s
    clearInterval(checkForShittyJquery);
  }
}, 50);

function bootstrapAbwebForm($) {
  $('form.custom-form').each(function () {
    var form = $(this);

    form.find('[data-required-checkbox]').each(function () {
      var name = $(this).attr('name');

      $(this).on('change', function () {
        var siblings = form.find('[data-required-checkbox][name="' + name + '"]');
        if (!siblings.is(':checked')) {
          siblings.closest('.abf-checkbox-options').removeClass('checkboxes-valid').addClass('checkboxes-invalid').attr('title', 'You must select at least one');
        } else {
          siblings.closest('.abf-checkbox-options').removeClass('checkboxes-invalid').addClass('checkboxes-valid').attr('title', '');
        }
      });
    });

    var classes = {
      label_success: form.attr('data-label-success-class'),
      label_error: form.attr('data-label-error-class'),
      field_success: form.attr('data-field-success-class'),
      field_error: form.attr('data-field-error-class'),
      form_success: form.attr('data-form-success-class'),
      form_error: form.attr('data-form-error-class'),
    };

    function resetClasses() {
      form.removeClass(classes.form_success).removeClass(classes.form_error);
      form.find('.form-field-error-message').removeClass(classes.label_error).removeClass(classes.label_success);
      form.find('.custom-form-input input, .custom-form-input select, .custom-form-input textarea').removeClass(classes.field_error).removeClass(classes.field_success);
    }

    function resetClassesForField(field) {
      field.closest('.custom-form-input').find('.form-field-error-message').removeClass(classes.label_error).removeClass(classes.label_success).html('');
      field.removeClass(classes.field_error).removeClass(classes.field_success);
    }

    form.find('input:visible, select, textarea').on('blur', function () {
      var field = $(this);
      var postData = {
        '__field': $(this).attr('name'),
        '__value': $(this).val(),
      };

      field.request('onFieldValidate', {
        data: postData,
        success: function (d) {
          resetClassesForField(field);

          if (d.success) {
            field.addClass(classes.field_success);
            field.closest('.custom-form-input').find('.form-field-error-message').addClass(classes.label_success);
          } else {
            field.addClass(classes.field_error);
            field.closest('.custom-form-input').find('.form-field-error-message').addClass(classes.label_error);
            field.closest('.custom-form-input').find('.form-field-error-message').html(d.error);
          }
        },
        error: function (xhr) {
          resetClassesForField(field);

          field.addClass(classes.field_error);
          field.closest('.custom-form-input').find('.form-field-error-message').addClass(classes.label_error);

          if (xhr.responseJSON.success !== undefined) {
            field.closest('.custom-form-input').find('.form-field-error-message').html(xhr.responseJSON.error).show();
          } else {
            field.closest('.custom-form-input').find('.form-field-error-message').html(xhr.responseText).show();
          }
        }
      });
    });

    form.on('submit', function () {

      if (form.is('.form-submitting')) {
        return false;
      }

      form.find('.form-field-error-message, .form-error-message').html('').hide();
      form.addClass('form-submitting');

      function handleErrors(errors) {
        for (var fieldName in errors) {
          var error = errors[fieldName];

          // can't start with dot, so if there's a dot with index > 0 then it's an option
          if (fieldName.indexOf('.') > 0) {
            fieldName = fieldName.replace(/\.(\d+)/, '[$1]');
          }

          var field = form.find('[name="' + fieldName + '"]');

          if (fieldName === 'g-recaptcha-response') {
            field = $('#g-recaptcha-response').closest('[data-sitekey]');
          }

          var msg = field.parent().find('.form-field-error-message');

          field.addClass(classes.field_error);
          msg.css('display', 'block').html(error).addClass(classes.label_error);
        }

        form.find('.custom-form-input input, .custom-form-input select, .custom-form-input textarea').each(function () {
          var name = $(this).attr('name');
          if (errors[name] === undefined) {
            // successful field
            $(this).addClass(classes.field_success);
            $(this).parent().find('.form-field-error-message').addClass(classes.label_success);
          }
        });

      }

      function handleError(error) {
        $.oc.flashMsg({
          'text': error,
          'class': 'error'
        });
      }

      form.request('onFormSubmit', {
        success: function (d) {
          resetClasses();

          if (d.success) {
            form.addClass(classes.form_success);

            if (d.action === 'redirect') {
              // redirect to url
              location.href = d.url;
              // location.replace(d.url);
            } else if (d.action === 'hide') {
              // hide the form
              form.slideUp('fast');
              form.removeClass('form-submitting');
              $.oc.flashMsg({
                'text': (d.message) ? d.message : 'Form successfully submitted',
                'class': 'success'
              });
            } else {
              // clear the form inputs
              form.find('input, select, textarea').val('');
              form.find('input').val('');
              form.removeClass('form-submitting');
              $.oc.flashMsg({
                'text': (d.message) ? d.message : 'Form successfully submitted',
                'class': 'success'
              });
            }
          } else {
            form.removeClass('form-submitting');
            form.addClass(classes.form_error);

            if (d.errors !== undefined) {
              handleErrors(d.errors);
            } else if (d.error !== undefined) {
              handleError(d.error);
            } else {
              handleError('An unexpected error occurred.');
            }
          }
        },
        error: function (xhr) {
          resetClasses();

          form.removeClass('form-submitting');
          form.addClass(classes.form_error);

          if (xhr.responseJSON && xhr.responseJSON.success === false) {
            var d = xhr.responseJSON;
            if (d.errors !== undefined) {
              handleErrors(d.errors);
            } else {
              handleError(d.error || xhr.responseText);
            }
            return;
          } else {
            handleError(xhr.responseText);
          }

          $.oc.flashMsg({
            'text': xhr.responseText,
            'class': 'error'
          });
        }
      });

      return false;
    });
  });
}
