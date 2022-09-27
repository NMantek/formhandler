import { load } from 'recaptcha-v3';

export class ReCaptchaSubmit {
  private containerList: NodeListOf<HTMLFormElement>;
  private siteKey: string;

  constructor(container: NodeListOf<HTMLFormElement>) {
    this.containerList = container;
    this.siteKey = '';

    this.containerList.forEach((container) => {
      this.siteKey = String(
        (<HTMLInputElement>container.querySelector('#ReCaptchaField'))?.dataset.sitekey
      );

      if (!this.siteKey) {
        return;
      }

      container.addEventListener('submit', this.handler, { once: true });
    });
  }

  private handler = (e: Event) => {
    console.log('triggered!');
    e.preventDefault();
    const target = e.target as HTMLFormElement;
    const captchaField = target.querySelector('#ReCaptchaField') as HTMLInputElement;

    load(this.siteKey).then((recaptcha) => {
      recaptcha.execute('submit').then((token) => {
        captchaField.value = token;

        target.submit();
      });
    });
  };
}
