/**
 * Handfill theme scripts.
 *
 * Everything else (menus, sliders, mini cart, password reveal…) is inherited
 * from Shofy's theme.js — only behaviour that Shofy does not ship lives here.
 */

/**
 * Password strength meter on the register form.
 *
 * Mirrors the reference design: 4 segments scored on length / uppercase /
 * digit / symbol, shown only once the user starts typing.
 */
function initPasswordStrength() {
    const labels = window.handfillPasswordStrengthLabels || {}

    document.querySelectorAll('[data-hf-password-strength]').forEach((meter) => {
        const target = document.querySelector(meter.dataset.hfPasswordStrength)

        if (!target) {
            return
        }

        const bars = meter.querySelectorAll('.hf-password-strength__bar')
        const label = meter.querySelector('.hf-password-strength__label')

        const score = (value) => {
            if (!value) {
                return 0
            }

            let result = 0

            if (value.length >= 8) result++
            if (/[A-Z]/.test(value)) result++
            if (/[0-9]/.test(value)) result++
            if (/[^A-Za-z0-9]/.test(value)) result++

            return result
        }

        const render = () => {
            const value = score(target.value)

            meter.classList.toggle('is-visible', Boolean(target.value))
            meter.dataset.score = String(value)

            bars.forEach((bar, index) => bar.classList.toggle('is-on', index < value))

            if (label) {
                label.textContent = labels[value] || ''
            }
        }

        target.addEventListener('input', render)
        render()
    })
}

/**
 * Live "passwords do not match" feedback on the confirmation field.
 */
function initPasswordConfirmation() {
    document.querySelectorAll('[data-hf-password-confirm]').forEach((feedback) => {
        const password = document.querySelector(feedback.dataset.hfPasswordConfirm)
        const confirmation = document.querySelector(feedback.dataset.hfPasswordConfirmTarget)

        if (!password || !confirmation) {
            return
        }

        const render = () => {
            const mismatch = Boolean(confirmation.value) && confirmation.value !== password.value

            feedback.hidden = !mismatch
            confirmation.classList.toggle('is-invalid', mismatch)
        }

        password.addEventListener('input', render)
        confirmation.addEventListener('input', render)
        render()
    })
}

function init() {
    initPasswordStrength()
    initPasswordConfirmation()
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
} else {
    init()
}
