// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {ChangeDetectorRef, Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {ActivatedRoute} from '@angular/router';
import {getURLParam} from '../../services/mappingUtil';
import {getUser, sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {Ticket} from '../../models/Ticket';
import {RefreshPage} from '../../models/RefreshPage';
import {TicketSubInputChangeEvent} from '../../components/ticketblock/ticket-block.component';


@Component({
	selector: 'ticket',
	templateUrl: './ticket.page.html',
	styleUrls: ['./ticket.page.scss']
})

export class TicketPage extends RefreshPage implements OnInit {

	@ViewChild('rows') rowsElement: ElementRef;

	public page: string;
	public from: string;
	public openTickets: Ticket[];
	public closedTickets: Ticket[];
	public aroundTickets: Ticket[];
	public lookalikeTickets: Ticket[] = [];

	public customFields = {};
	public titleDescObject = {count: 0, name: undefined, content: undefined};

	constructor(
		private httpClient: HttpClient,
		private route: ActivatedRoute,
		private cRef: ChangeDetectorRef) {
		super(60);
	}

	ngOnInit() {
		super.ngOnInit();
		const thiz = this;
		this.route.params.subscribe(page => thiz.setPage(page));
	}

	onRefresh() {
		this.leftMenu();
		this.findMatchingTickets(false);
	}

	leftMenu() {
		const thiz = this;
		this.requestTicketList('open', ticketlist => {
			thiz.openTickets = ticketlist;
		});
		this.requestTicketList('closed', ticketlist => {
			thiz.closedTickets = ticketlist;
		});
		this.requestTicketList('around', ticketlist => {
			thiz.aroundTickets = ticketlist;
		});
	}

	setPage(page) {
		const thiz = this;
		this.page = page.page;
		this.from = getURLParam('FullName');
		if (this.from) {
			this.from = atob(this.from);
		} else {
			getUser(user => {
				thiz.from = user.realname + ' ' + user.firstname;
				thiz.cRef.markForCheck();
				thiz.cRef.detectChanges();
			});
			this.from = '';
		}
		this.leftMenu();
	}

	findMatchingTickets(withReload = true) {
		if (withReload) {
			this.lookalikeTickets = [];
		}

		const cloneTitleDescObject = JSON.parse(JSON.stringify(this.titleDescObject));
		for (const field of Object.keys(this.customFields)) {
			if (!cloneTitleDescObject.content) {
				cloneTitleDescObject.content = '';
			}
			cloneTitleDescObject.content += '\n' + field + ': ' + this.customFields[field];
		}

		sendSecureHeader(headers => {
			headers = headers
				.set('Type', 'search');
			this.httpClient.post<Ticket[]>(GLOBAL.api + '/Tickets',
				cloneTitleDescObject,
				{headers}).toPromise()
				.then(tickets => this.lookalikeTickets = tickets)
				.catch(console.error);
		});
	}

	requestTicketList(type: 'open' | 'closed' | 'around', callback: (ticketlist: Ticket[]) => void) {
		const thiz = this;
		sendSecureHeader(headers => {
			headers = headers
				.set('Type', type);
			thiz.httpClient.get<Ticket[]>(GLOBAL.api + '/Tickets',
				{headers}).toPromise()
				.then(callback)
				.catch(console.error);
		});
	}

	toDate(input: string) {
		return new Date(input).toLocaleDateString();
	}

	onTicketCreate() {
		this.leftMenu();
	}

	checkRightMenu(event: TicketSubInputChangeEvent) {
		if (event.field === 'description') {
			this.titleDescObject.content = event.value;
		} else if (event.field === 'title') {
			this.titleDescObject.name = event.value;
		} else if (event.field.indexOf('custom.') === 0) {
			if (event.value == null || event.value === '') {
				delete this.customFields[event.field.replace('custom.', '')];
			} else {
				this.customFields[event.field.replace('custom.', '')] = event.value;
			}
			return;
		} else {
			return;
		}
		this.titleDescObject.count++;
		if (this.titleDescObject.name === undefined || this.titleDescObject.content === undefined) {
			return;
		}
		if (this.titleDescObject.count >= 2) {
			this.findMatchingTickets();
		}
	}
}

