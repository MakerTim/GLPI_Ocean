import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {FormsModule} from '@angular/forms';
import {HttpClientModule} from '@angular/common/http';
import {ScrollToModule} from '@nicky-lenaers/ngx-scroll-to';
import {DragDropModule} from '@angular/cdk/drag-drop';
import {SnackbarModule} from 'ngx-snackbar';

import {appRoutingProviders, routing} from './app.routing';
import {I18n} from './pipes/translator';
import {MD} from './pipes/md';
import {BR, Debug, Dump} from './pipes/debug';
import {Reverse} from './pipes/reverse';
import {Limit} from './pipes/limit';

import {AppComponent} from './app.component';
import {SwitchComponent} from './components/switch/switch.component';
import {TimeComponent} from './components/time/time.component';
import {InputboxComponent} from './components/inputbox/inputbox.component';
import {TicketInputComponent} from './components/ticketinput/ticket-input.component';
import {TicketBlockComponent} from './components/ticketblock/ticket-block.component';

import {HomePage} from './pages/home/home.page';
import {MenupagePage} from './pages/menupage/menupage.page';
import {ConcretePagePage} from './pages/concretepage/concretepage.page';
import {ListallPage} from './pages/listall/listall.page';
import {TicketPage} from './pages/ticket/ticket.page';
import {OpenTicketPage} from './pages/openticket/openticket.page';
import {TicketAdminPage} from './pages/ticket-admin/ticketadmin.page';
import {LogsPage} from './pages/logs/logs.page';
import {SettingsPage} from './pages/settings/settings.page';
import {NotfoundPage} from './pages/notfound/notfound.page';
import {RedirectPage} from './pages/redirect/redirect.page';
import {VarDirective} from './directives/ng-var.directive';
import {TicketMoverPage} from './pages/ticketmover/ticket-mover-page.component';
import {EmptyPage} from './pages/emtpy/empty.page';
import {DashboardPage} from './pages/dashboard/dashboard.page';
import {DashboardMenuPage} from './pages/dashboardmenu/dashboardmenu.page';
import {StripHtml} from './pipes/striphtml';
import {SearchticketPage} from './pages/searchticket/searchticket.page';

@NgModule({
	declarations: [
		I18n,
		Debug,
		Dump,
		BR,
		StripHtml,
		VarDirective,
		Reverse,
		Limit,
		MD,
		MenupagePage,
		HomePage,
		ConcretePagePage,
		RedirectPage,
		ListallPage,
		SettingsPage,
		TicketPage,
		TicketAdminPage,
		LogsPage,
		NotfoundPage,
		OpenTicketPage,
		TicketMoverPage,
		DashboardPage,
		DashboardMenuPage,
		SearchticketPage,
		AppComponent,
		SwitchComponent,
		TimeComponent,
		TicketInputComponent,
		InputboxComponent,
		TicketBlockComponent,
		EmptyPage,
	],
	imports: [
		BrowserModule,
		DragDropModule,
		NgbModule,
		routing,
		FormsModule,
		HttpClientModule,
		SnackbarModule.forRoot(),
		ScrollToModule.forRoot()
	],
	providers: [
		appRoutingProviders
	],
	bootstrap: [AppComponent],
	schemas: [VarDirective]
})
export class AppModule {
}
