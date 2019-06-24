// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {Location} from '@angular/common';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {ActivatedRoute} from '@angular/router';
import {getUserRaw, sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {Ticket} from 'src/app/models/Ticket';
import {
	closeTicket,
	followup,
	markSolution,
	newInternalCategory,
	readInput,
	sendAttachments,
	solution
} from '../../services/ticket.service';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {RefreshPage} from '../../models/RefreshPage';

@Component({
	selector: 'open-ticket',
	templateUrl: './openticket.page.html',
	styleUrls: ['./openticket.page.scss']
})

export class OpenTicketPage extends RefreshPage implements OnInit {

	public kaceURL = GLOBAL.kaceUrl;

	public leftMenuItems = [ //
		['date_creation'], ['date_mod'], ['closedate'], [], //

		['users_id_recipient'], ['requested_users', 'user'], ['requested_groups', 'group'], ['users_id_lastupdater'], //
		['assigned_users', 'user'], ['assigned_groups', 'group'], [], //

		['status'], ['urgency'], ['impact'], ['priority'], //
		['itilcategories_id'], ['requesttypes_id'], ['global_validation'], ['locations_id'], [], //

		['id'],
	];
	public centerTopMenuItems = [ //
		'name', '', //
		'content', '', //
	];
	public page: { id: string };
	public ticket: Ticket = new Ticket('...', '');
	public attachments = [];
	public actions = [];
	public sortActionsAscending = false;

	public solutionDirectories: any[] = [];
	public solutions: any[] = [];

	constructor(
		private httpClient: HttpClient,
		private modalService: NgbModal,
		private location: Location,
		private route: ActivatedRoute) {
		super(60);
	}

	ngOnInit() {
		super.ngOnInit();
		const thiz = this;
		this.route.params.subscribe((page: { id: string }) => thiz.setPage(page));
	}

	onRefresh() {
		this.refreshContent();
	}

	goBack() {
		this.location.back();
	}

	setPage(page: { id: string }) {
		this.page = page;
		this.refreshContent();
	}

	isHalting(): boolean {
		return super.isHalting() || this.modalService.hasOpenModals();
	}

	refreshContent() {
		sendSecureHeader(headers => {
			headers = headers.set('id', this.page.id);
			this.httpClient.get<Ticket>(GLOBAL.api + '/Ticket', {headers}).toPromise()
				.then(ticket => this.ticket = ticket);
			headers = headers.set('type', 'attachments');
			this.httpClient.get<[any]>(GLOBAL.api + '/Ticket', {headers}).toPromise()
				.then(attachments => {
					const newAttachments = [];
					const newActions = [];
					for (const attachment of attachments) {
						if (this.dateCompare(attachment[2]) < 100) {
							newAttachments.push(attachment);
						} else {
							newActions.push(attachment);
						}
					}
					this.refreshContentPhase2(headers, newAttachments, newActions);
				});
		});
	}

	refreshContentPhase2(headers: HttpHeaders, newAttachments, newActions) {
		this.sortActions();
		headers = headers.set('type', 'actions');
		this.httpClient.get<[{}]>(GLOBAL.api + '/Ticket', {headers}).toPromise()
			.then(actions => {
				actions.forEach(action => newActions.push(action));
				this.attachments = newAttachments;
				this.actions = newActions;
				this.sortActions();
			});
	}

	userIsPoster() {
		const user = getUserRaw();
		if (user == null || user.groups == null ||
			this.ticket == null || this.ticket.requested_users == null || this.ticket.requested_groups == null) {
			return false;
		}
		let contains = false;
		for (const requestUser of this.ticket.requested_users) {
			if (requestUser.id === user.id) {
				contains = true;
			}
		}
		for (const requestedGroups of this.ticket.requested_groups) {
			if (user.groups.indexOf(requestedGroups.id) >= 0) {
				contains = true;
			}
		}
		return contains || user.id === this.ticket.users_id_recipient_id;
	}

	userIsAssigned() {
		const user = getUserRaw();
		if (user == null || user.groups == null ||
			this.ticket == null || this.ticket.assigned_users == null || this.ticket.assigned_groups == null) {
			return false;
		}
		let contains = false;
		for (const assignedUser of this.ticket.assigned_users) {
			if (assignedUser.id === user.id) {
				contains = true;
			}
		}
		for (const assignedGroups of this.ticket.assigned_groups) {
			if (user.groups.indexOf(assignedGroups.id) >= 0) {
				contains = true;
			}
		}
		return contains;
	}

	isClosed() {
		return this.ticket.status_id >= 5;
	}

	hasOpenSolution() {
		let lastAction;
		for (const action of this.actions) {
			if (this.isArray(action)) {
				continue;
			}
			lastAction = action;
			if (!this.sortActionsAscending) { // if descending, its the first group
				break;
			}
		}
		return lastAction !== undefined && lastAction.type === 'solution' && lastAction.status === 'waiting';
	}

	toggleFixed(target: HTMLImageElement) {
		if (target.style.position !== 'fixed') {
			target.style.fontSize = target.style.maxHeight;
			target.style.position = 'fixed';
			target.style.maxHeight = '80vmin';
			target.style.left = '';
			target.style.display = 'flex';
		} else {
			target.style.display = 'initial';
			target.style.left = '12%';
			target.style.position = 'initial';
			target.style.maxHeight = target.style.fontSize;
		}
	}

	toCssClass(input: string) {
		return input.replace(/[^a-zA-Z0-9 -]/, '');
	}

	isArray(obj: any) {
		return Array.isArray(obj);
	}

	sortActions() {
		this.actions.sort((a, b) => {
			let dateA;
			let dateB;
			if (this.isArray(a)) {
				dateA = a[2];
			} else {
				dateA = a.date_mod;
			}
			if (this.isArray(b)) {
				dateB = b[2];
			} else {
				dateB = b.date_mod;
			}

			if (this.sortActionsAscending) {
				return new Date(dateA).getTime() - new Date(dateB).getTime();
			} else {
				return new Date(dateB).getTime() - new Date(dateA).getTime();
			}
		});
	}

	dateCompare(date: string) {
		const dateTicketCreation = new Date(this.ticket.date_creation);
		const dateAttachmentCreation = new Date(date);

		return Math.floor((dateAttachmentCreation.getTime() - dateTicketCreation.getTime()) / 1000);
	}

	refresh() {
		this.setPage(this.page);
	}

	followup(followupHTML) {
		followup(this.modalService, followupHTML, this.httpClient, this.ticket, () => {
			this.refresh();
		});
	}

	async attach(element: HTMLInputElement) {
		const file = await readInput(element);

		sendAttachments(this.httpClient, this.ticket, [[file]], onProgress => {
			if (onProgress === null) {
				this.refresh();
			}
		});
	}

	preCloseTicket() {
		closeTicket(this.httpClient, this.ticket, () => {
			this.refresh();
		});
	}

	getSolutionDirs() {
		sendSecureHeader(headers => {
			headers = headers
				.set('type', 'categories');
			this.httpClient.get<any[]>(GLOBAL.api + '/KnowledgeBase', {headers}).toPromise()
				.then(solutionDirectories => {
					this.solutionDirectories = solutionDirectories;
				});
		});
	}

	proposeSolution(solutionHTML) {
		this.solutions = [];
		this.getSolutionDirs();
		solution(this.modalService, solutionHTML, this.httpClient, this.ticket, {
			centered: true,
			size: 'lg'
		}, (modalArray) => {
			const modal = modalArray[2];
			console.log(modal);
			this.postSelectedSolution(modal.private, modal);
			this.refresh();
		});
	}

	markSolution(followupHTML, accepted: boolean) {
		markSolution(this.modalService, followupHTML, this.httpClient, this.ticket, accepted, () => {
			this.refresh();
		});
	}

	selectSolutionDir(value: any, modal) {
		modal.internalSelected = 'null';
		modal.privateObject = null;
		modal.category = value;
		sendSecureHeader(headers => {
			headers = headers
				.set('type', 'solutions')
				.set('category', value);
			this.httpClient.get<any[]>(GLOBAL.api + '/KnowledgeBase', {headers}).toPromise()
				.then(solutions => {
					this.solutions = solutions;
				});
		});
	}

	selectSolution(value: any, modal) {
		if (value == null || !(value in this.solutions)) {
			modal.privateObject = null;
			return;
		}
		sendSecureHeader(headers => {
			headers = headers
				.set('type', 'solution')
				.set('id', this.solutions[value].id)
				.set('ticketId', this.ticket.id.toString());
			this.httpClient.get<any>(GLOBAL.api + '/KnowledgeBase', {headers}).toPromise()
				.then(foundSolution => {
					modal.privateObject = foundSolution;
					modal.private = foundSolution.answer;
				});
		});
	}

	createNewCategory(html) {
		newInternalCategory(this.modalService, html, this.httpClient, () => {
			this.getSolutionDirs();
		});
	}

	postSelectedSolution(content: any, modal) {
		sendSecureHeader(headers => {
			if (modal.privateObject != null) { // update existing
				headers = headers.set('id', modal.privateObject.id.toString());
			}
			headers = headers.set('type', 'postSolution')
				.set('categoryId', modal.category)
				.set('name', modal.privateTitle ? modal.privateTitle : modal.privateObject.name)
				.set('ticketId', this.ticket.id.toString());
			this.httpClient.post<any>(GLOBAL.api + '/KnowledgeBase', content, {headers}).toPromise()
				.then(foundSolution => {
					modal.privateObject = foundSolution;
					modal.private = foundSolution.answer;
				});
		});
	}
}
