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

  model: AppModel = new AppModel('usd', 'single', 'pay-1');

  amounts: Array<string>;

  constructor(private http: HttpClient) {
    this.updateAmounts();
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

  clickCard() {
    const data = {
      'token': 'some_token'
    }
    this.http.post('http://localhost:8080/api/pay', data, {
      headers: new HttpHeaders().set('Content-Type', 'application/json'),
      observe: 'response',
    }).subscribe(data => {
      console.log(data.status, data.body);
    });
    return false;
  };

  clickPaypal() {

  };

}
