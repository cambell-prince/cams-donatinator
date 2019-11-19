import { NgModule } from '@angular/core';

import { BrowserModule } from '@angular/platform-browser';
import { FormsModule } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';

import { RecaptchaModule } from 'ng-recaptcha';
import { RecaptchaFormsModule } from 'ng-recaptcha/forms';

import { AppComponent } from './app.component';
import { UnitsPipe } from './units.pipe';

@NgModule({
  declarations: [
    AppComponent,
    UnitsPipe
  ],
  imports: [
    BrowserModule,
    RecaptchaModule.forRoot(),
    RecaptchaFormsModule,
    FormsModule,
    HttpClientModule
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
