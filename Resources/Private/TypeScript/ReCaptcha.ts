import { ReCaptchaSubmit } from './modules/ReCaptchaSubmit';

document.addEventListener('DOMContentLoaded', () => {
  const recaptchaForm = document.querySelectorAll('form') as NodeListOf<HTMLFormElement>;
  if (null !== recaptchaForm) {
    new ReCaptchaSubmit(recaptchaForm);
  }
});
