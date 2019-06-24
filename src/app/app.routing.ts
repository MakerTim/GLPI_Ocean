import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
// Pages
import {HomePage} from './pages/home/home.page';
import {MenupagePage} from './pages/menupage/menupage.page';
import {NotfoundPage} from './pages/notfound/notfound.page';
import {ConcretePagePage} from './pages/concretepage/concretepage.page';
import {ListallPage} from './pages/listall/listall.page';
import {TicketPage} from './pages/ticket/ticket.page';
import {LogsPage} from './pages/logs/logs.page';
import {SettingsPage} from './pages/settings/settings.page';
import {TicketAdminPage} from './pages/ticket-admin/ticketadmin.page';
import {OpenTicketPage} from './pages/openticket/openticket.page';
import {TicketMoverPage} from './pages/ticketmover/ticket-mover-page.component';
import {DashboardPage} from './pages/dashboard/dashboard.page';
import {DashboardMenuPage} from './pages/dashboardmenu/dashboardmenu.page';
import {RedirectPage} from './pages/redirect/redirect.page';
import {SearchticketPage} from './pages/searchticket/searchticket.page';
import {UserPage} from './pages/user/user.page';

const appRoutes: Routes = [
	{path: '', component: HomePage},
	{path: 'home', component: HomePage},
	{path: 'page/:page', component: MenupagePage},
	{path: 'concrete/Ticket', component: TicketPage},
	{path: 'concrete/:page', component: ConcretePagePage},
	{path: 'admin/user/:id', component: UserPage},
	{path: 'admin/logs', component: LogsPage},
	{path: 'admin/settings', component: SettingsPage},
	{path: 'admin/ticket-form', component: TicketAdminPage},
	{path: 'assets/:page', component: ListallPage},
	{path: 'open/Ticket/:id', component: OpenTicketPage},
	{path: 'global/Ticket', component: TicketMoverPage},
	{path: 'group/Ticket/:id', component: TicketMoverPage},
	{path: 'dashboard/Ticket', component: DashboardMenuPage},
	{path: 'dashboard/Ticket/:id', component: DashboardPage},
	{path: 'dashboard/Ticket/user/:id', component: DashboardPage},
	{path: 'search/Ticket', component: SearchticketPage},
	{path: 'front/:redirect', component: RedirectPage},
	{path: 'kace/front/:redirect', component: RedirectPage},
	{path: '**', component: NotfoundPage}
];

export const appRoutingProviders: any[] = [];
export const routing: ModuleWithProviders = RouterModule.forRoot(appRoutes);
