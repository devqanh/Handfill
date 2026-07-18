'use strict'

document.addEventListener('DOMContentLoaded', function() {
    var selectedRadio = document.querySelector('input[name="payment_method"]:checked');
    if (selectedRadio) {
        var collapse = selectedRadio.closest('.list-group-item').querySelector('.payment_collapse_wrap');
        if (collapse) {
            collapse.classList.add('show', 'active');
        }
    }

    document.querySelectorAll('.js_payment_method').forEach(function(radio) {
        radio.addEventListener('change', function(e) {
            document.querySelectorAll('.payment_collapse_wrap').forEach(function(el) {
                el.classList.remove('collapse', 'show', 'active');
            });

            var collapse = e.target.closest('.list-group-item').querySelector('.payment_collapse_wrap');
            if (collapse) {
                collapse.classList.add('show', 'active');
            }
        });
    });
});
