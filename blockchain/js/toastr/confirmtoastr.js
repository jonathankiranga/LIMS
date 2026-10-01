function smartalert(callback, options = {}) {
  const message = options.message || 'Are you sure you want to perform this action?';
  const yesLabel = options.yesLabel || 'Yes';
  const noLabel = options.noLabel || 'No';
  const yesToast = options.yesToast || null;
  const noToast = options.noToast || null;

  toastr.options = {
    closeButton: true,
    positionClass: 'toast-top-center',
    timeOut: '0',
    extendedTimeOut: '0',
    onclick: null,
    tapToDismiss: false,
    onCloseClick: function () {
      return false;
    },
    progressBar: false,
    preventDuplicates: true,
    showEasing: 'swing',
    hideEasing: 'linear',
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut',
  };

  toastr.warning(
    `${message}<br />
<button type="button" class="btn confirm-yes">${yesLabel}</button>
<button type="button" class="btn confirm-no">${noLabel}</button>`,
    '',
    {
      timeOut: '0',
      extendedTimeOut: '0',
      onShown: function () {
        var toast = $('.toast:last-child');
        toast.find('.confirm-yes').on('click', function () {
          if (yesToast) {
            toastr.success(yesToast);
          }
          callback(true);
          toastr.clear(toast, { force: true });
        });
        toast.find('.confirm-no').on('click', function () {
          if (noToast) {
            toastr.info(noToast);
          }
          callback(false);
          toastr.clear(toast, { force: true });
        });
      },
    }
  );

  // Prevent default behavior of buttons
  $(document).off('click.confirmToastr', '.confirm-yes, .confirm-no')
    .on('click.confirmToastr', '.confirm-yes, .confirm-no', function (e) {
    e.preventDefault();
  });
}

function showConfirm(callback, options = {}) {
  return smartalert(callback, options);
}
