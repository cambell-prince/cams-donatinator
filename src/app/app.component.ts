import { Component } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { AppModel } from './app.model';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent {

  currencies = [ 'usd', 'aud', 'eur', 'gbp', 'nzd', 'thb' ];

  currencySymbols = {
    'usd': '$',
    'aud': '$',
    'eur': '€',
    'gbp': '£',
    'nzd': '$',
    'thb': '฿',    
  }

  model: AppModel = new AppModel('usd', 'single', 0);

  amounts: Array<string>;

  private stripe;

  constructor(private http: HttpClient) {
    var self = this;
    this.updateAmounts();
    this.stripe = (<any>window).StripeCheckout.configure({
      key: 'pk_test_gyAceo53YlSpl7gQDV4PjMfS',
      image: 'https://stripe.com/img/documentation/checkout/marketplace.png',
      locale: 'auto',
      token: function(token, tokenData) {
        console.log('got token', token, 'data ', tokenData);
        // You can access the token ID with `token.id`.
        // Get the token ID to your server-side code for use.
        const postData = {
          'token': token,
          'amount': self.amountForCard(),
          'currency': self.model.currency,
          'data': tokenData,
        }
        self.http.post('http://localhost:8080/api/pay/stripe', postData, {
          headers: new HttpHeaders().set('Content-Type', 'application/json'),
          observe: 'response',
        }).subscribe(data => {
          // TODO Process the response, give thanks, and redirect.
          console.log(data.status, data.body);
        });
      }
    });
  }

  updateAmounts() {
    var onceAmounts = {
      'usd': ['20', '50', '100', '200'],
      'aud': ['30', '50', '100', '200'],
      'eur': ['20', '50', '100', '200'],
      'gbp': ['20', '50', '100', '200'],
      'nzd': ['20', '50', '100', '200'],
      'thb': ['500', '1000', '2000', '3000'],
    };
    var monthlyAmounts = {
      'usd': ['10', '20', '50', '5'],
      'aud': ['10', '20', '50', '5'],
      'eur': ['10', '50', '100', '5'],
      'gbp': ['5', '20', '40', '3'],
      'nzd': ['10', '20', '50', '5'],
      'thb': ['200', '400', '500', '100'],
    };
    if (this.model.frequency == 'monthly') {
      this.amounts = monthlyAmounts[this.model.currency];
    } else {
      this.amounts = onceAmounts[this.model.currency];      
    }
  };

  amountForCard() {
    // console.log(this.model.paySelection);
    if (this.model.paySelection >= 0 && this.model.paySelection < 4) {
      return Number(this.amounts[this.model.paySelection]) * 100;
    }
    if (Number(this.model.payAmount) > 0) {
      return Number(this.model.payAmount) * 100;
    }
    return 2000;
  }

  clickCard() {
    this.stripe.open({
      name: 'Hannah',
      description: 'Donation to Hannah Prince',
      amount: this.amountForCard(),
      currency: this.model.currency,
      allowRememberMe: false
    });
    return false;
  };

  clickPaypal() {

  };

}
