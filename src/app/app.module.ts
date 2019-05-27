import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';

import {AppRoutingModule} from './app-routing.module';
import {AppComponent} from './app.component';
import {HttpClientModule} from '@angular/common/http';
import { ReportComponent } from './report/report.component';
import {HotTableModule} from '@handsontable/angular';
import {Debug} from './debug';
import {ChartsModule} from 'ng2-charts';
import {GraphComponent} from './graph/graph.component';

@NgModule({
    declarations: [
        AppComponent,
        ReportComponent,
        GraphComponent,
        Debug,
    ],
    imports: [
        BrowserModule,
        AppRoutingModule,
        HttpClientModule,
        HotTableModule.forRoot(),
        ChartsModule,
    ],
    providers: [],
    bootstrap: [AppComponent]
})
export class AppModule {
}
