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
import {Observable} from 'rxjs';

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
		['itilcategories_id'], ['locations_id'], [], //

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
	public imgCache = {};

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

	getPlatformURL() {
		if (getUserRaw() && getUserRaw().permissions.helpdesk === 'WRITE') {
			return 'adminui';
		} else {
			return 'userui';
		}
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
			this.httpClient.get<Ticket>(GLOBAL.custom + '/Ticket', {headers}).toPromise()
				.then(ticket => {
					this.ticket = ticket;
					this.httpClient.get<any>
					(GLOBAL.api + '/service_desk/tickets/' + this.page.id + '/changes?shaping=hd_ticket_change regular,attachments regular,user limited', {
						headers,
						withCredentials: true
					}).toPromise()
						.then(changes => {
							changes.Changes.shift();
							if (!this.sortActionsAscending) {
								changes.Changes = changes.Changes.reverse();
							}
							this.actions = changes.Changes;
						});
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
		if (user == null || this.ticket == null || this.ticket.requested_users == null) {
			return false;
		}
		let contains = false;
		for (const requestUser of this.ticket.requested_users) {
			if (requestUser.ID === user.userId) {
				contains = true;
			}
		}
		return contains || user.userId === this.ticket.users_id_recipient_id;
	}

	userIsAssigned() {
		const user = getUserRaw();
		if (user == null || this.ticket == null || this.ticket.assigned_users == null) {
			return false;
		}
		let contains = false;
		for (const assignedUser of this.ticket.assigned_users) {
			if (assignedUser.ID === user.userId) {
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
		if (input) {
			return input.replace(/[^a-zA-Z0-9 -]/, '');
		}
		return '';
	}

	isArray(obj: any) {
		return Array.isArray(obj);
	}

	sortActions() {
		this.actions = this.actions.reverse();
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
		// @ts-ignore
		followup(this.modalService, followupHTML, this.httpClient, this.ticket.HD_QUEUE_ID, this.ticket.ID, () => {
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

	openImageInNewWindow(actionId: number, attachmentId: number) {
		let placeholder = true;
		this.getImage(actionId, attachmentId).subscribe(img => {
			if (placeholder) {
				placeholder = false;
				return;
			}
			const win = window.open();
			win.document.write(
				'<iframe src="' + img +
				'" style="border:0;top:0;left:0;bottom:0;right:0;width: calc(100% + 16px);' +
				'height: calc(100% + 16px);margin: -8px -8px;" allowfullscreen></iframe>');

			const obj = {Page: 'imgage ' + actionId + ':' + attachmentId, Url: './' + this.page.id};
			win.window.history.pushState(obj, obj.Page, obj.Url);
			win.window.document.title = 'Image from ticket ' + this.page.id +
				' - ' + actionId + ':' + attachmentId;
		});
	}

	getImage(actionId: number, attachmentId: number): Observable<any> {
		const key = actionId + '-' + attachmentId;
		if (Object.keys(this.imgCache).indexOf(key) >= 0) {
			return this.imgCache[key];
		}
		return this.imgCache[key] = new Observable(observer => {
			observer.next('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
			sendSecureHeader(headers => {
				this.httpClient.get<any>(GLOBAL.api + '/service_desk/tickets/' + this.page.id +
					'/changes/' + actionId + '/attachments/' + attachmentId, {
					headers,
					withCredentials: true,
					// @ts-ignore
					responseType: 'blob'
				}).subscribe(sub => {
					const reader = new FileReader();
					reader.addEventListener('load', () => {
						observer.next(reader.result);
						observer.complete();
					});
					reader.readAsDataURL(sub);
				});
			});
		});
	}
}
