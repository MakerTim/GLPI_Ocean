// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnDestroy, OnInit} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {ActivatedRoute, Router} from '@angular/router';
import {Ticket} from '../../models/Ticket';
import {sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {RefreshPage} from '../../models/RefreshPage';

@Component({
	selector: 'dashboard',
	templateUrl: './dashboard.page.html',
	styleUrls: ['./dashboard.page.scss']
})

export class DashboardPage extends RefreshPage implements OnInit, OnDestroy {
	public states = [
		['new', 'assign'], //
		['waiting', 'plan', 'test', 'qualification'], //
		['closed', 'solved', 'observe', 'evaluation', 'approbation', 'accepted']]; //

	private priorityClassScale = ['none', 'normal', 'abnormal', 'high', 'higher', 'panic'];
	public priorityScale = ['lowest', 'low', 'medium', 'high', 'highest'];
	public priorityTiming = [20160, 10080, 4320, 1440, 60];

	public tickets: Ticket[] = [];
	public pageId = '0';
	public isUser = false;

	constructor(
		private httpClient: HttpClient,
		private route: ActivatedRoute,
		private router: Router) {
		super(60);
	}

	ngOnInit() {
		this.setNavbar(true);
		this.route.params.subscribe(page => {
			this.pageId = page.id;
			this.isUser = this.router.url.indexOf('user') === 18 || this.router.url.indexOf('self') === 18;
			this.loadDashboard();
		});
		super.ngOnInit();
	}

	ngOnDestroy() {
		this.setNavbar(false);
		super.ngOnDestroy();
	}

	getTicketCount(state) {
		let count = 0;
		if (!this.tickets || !this.tickets.filter) {
			return count;
		}
		this.tickets
			.filter(ticket => state.indexOf(ticket.status) >= 0)
			.forEach(ticket => {
				count++;
			});
		return count;
	}

	onRefresh() {
		this.loadDashboard();
	}

	loadDashboard() {
		sendSecureHeader(headers => {
			headers = headers //
				.set('id', this.pageId.toString()) //
				.set('type', this.isUser ? 'user' : 'group');
			this.httpClient.get<Ticket[]>(GLOBAL.api + '/Dashboard', {headers}).toPromise()
				.then(response => {
					this.tickets = response;
				});
		});
	}

	setNavbar(b: boolean) {
		const nav = document.getElementsByTagName('nav')[0];
		nav.style.height = b ? '0' : '';
		nav.style.zIndex = b ? '1' : '';
		nav.style.padding = b ? '0' : '';
		nav.style.border = b ? '0' : '';
		nav.style.top = b ? '20px' : '';
	}

	getPriority(ticket: Ticket) {
		if (this.isUser) {
			if (this.states[2].indexOf(ticket.status) >= 0) {
				return 'closed';
			}
			return 'none';
		}
		const dateMod = new Date(ticket.date_mod);
		const dateNow = new Date();

		const minAgo = (dateNow.getTime() - dateMod.getTime()) / 1000 / 60;
		const priorityScaleIndex = this.priorityScale.indexOf(ticket.priority);
		let ticketTime = this.priorityScale.indexOf(ticket.priority);
		if (ticketTime < 0) {
			ticketTime = 0;
		}
		const maxTime = this.priorityTiming[ticketTime];

		if (this.states[2].indexOf(ticket.status) >= 0) {
			return 'closed';
		}

		let priority = 0;
		const percent = (minAgo / maxTime) * 100;
		if (percent > 90) {
			priority = 5;
		} else if (percent > 80) {
			priority = 4;
		} else if (percent > 75) {
			priority = 3;
		} else if (percent > 50) {
			priority = 2;
		} else if (percent > 30) {
			priority = 1;
		}

		return this.priorityClassScale[priority];
	}

	sortPriority(tickets: Ticket[]) {
		if (!tickets || !tickets.sort) {
			return [];
		}
		return tickets.sort((a, b) => {
			const indexA = this.priorityClassScale.indexOf(this.getPriority(a));
			const indexB = this.priorityClassScale.indexOf(this.getPriority(b));

			return indexB - indexA;
		});
	}

	requested(ticket) {
		if (ticket.requested_users.length === 0) {
			return ticket.users_id_recipient;
		}
		return ticket.requested_users.map(userObj => userObj.requested_users.split(' ')[0]).join(', ');
	}

	assigned(ticket) {
		if (ticket.assigned_users.length === 0) {
			if (ticket.users_id_lastupdater) {
				return ticket.users_id_lastupdater;
			}
			return ticket.assigned_groups.map(groupObj => groupObj.assigned_groups).join(', ');
		}
		return ticket.assigned_users.map(userObj => userObj.assigned_users.split(' ')[0]).join(', ');
	}
}
