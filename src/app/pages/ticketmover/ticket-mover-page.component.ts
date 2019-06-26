/* tslint:disable */
// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {Location} from '@angular/common';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {ActivatedRoute} from '@angular/router';
import {CdkDragDrop, transferArrayItem} from '@angular/cdk/drag-drop';
import {getUser, sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {RefreshPage} from 'src/app/models/RefreshPage';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';

@Component({
	selector: 'global-ticket',
	templateUrl: './ticket-mover-page.component.html',
	styleUrls: ['./ticketmover.page.scss']
})

export class TicketMoverPage extends RefreshPage implements OnInit {
	public unassignedTickets = {};
	public assignedTickets = {};

	public popupFields = [ //
		'requested_users', 'requested_groups', 'assigned_users', 'assigned_groups', 'followed_groups'
	];

	public dropListsIds: string[] = [];
	public loading = false;
	public selectedType: 'group' | 'global';
	private groupId: string;
	public group = {id: 0, name: '', completename: '', users: [], tickets: []};
	public movingTicket = null;

	constructor(
		private httpClient: HttpClient,
		private location: Location,
		private modalService: NgbModal,
		private route: ActivatedRoute) {
		super(60);
	}

	ngOnInit() {
		super.ngOnInit();
		this.route.url.subscribe(url => {
			// @ts-ignore
			this.selectedType = url[0].path;
			this.dropListsIds = ['unassigned'];
			if (this.selectedType === 'group') {
				this.groupId = url[2].path;
				this.dropListsIds = ['group_' + this.groupId];
			}
			this.onRefresh();
		});
	}

	loadGroup() {
		sendSecureHeader((headers: HttpHeaders) => {
			headers = headers
				.set('id', this.groupId)
				.set('withTicket', '1');
			this.httpClient
				.get<{ id: number, name: string, completename: string, users: [], tickets: [] }>(GLOBAL.api + '/Group', {headers}).toPromise()
				.then(group => {
					this.group = group;
					this.unassignedTickets = this.group;
					group.users.forEach((user: any) => {
						user.type = 'User';
						const key = 'cdkList-user_' + user.id;
						if (this.dropListsIds.indexOf(key) === -1) {
							this.dropListsIds.push(key);
						}
					});
					this.retrieveOverview();
				});
		});
	}

	onRefresh() {
		if (this.selectedType === 'group') {
			this.loadGroup();
		} else {
			this.retrieveOverview();
		}
	}

	subArray(array, skipping = 1) {
		return array.slice(skipping, array.length);
	}

	objectKeys(obj) {
		return Object.keys(obj);
	}

	highlight(id, enable: boolean) {
		const sameTicketList = document.getElementsByClassName(id);
		for (let i = 0; i < sameTicketList.length; i++) {
			const sameTicket: HTMLElement = sameTicketList[i].parentElement;
			if (enable) {
				sameTicket.style.color = 'var(--background)';
				sameTicket.style.backgroundColor = 'var(--main-2)';
			} else {
				sameTicket.style.color = '';
				sameTicket.style.backgroundColor = '';
			}
		}
	}

	retrieveOverview() {
		sendSecureHeader((headers: HttpHeaders) => {
			if (this.selectedType === 'group') {
				headers = headers
					.set('subtype', 'group')
					.set('id', this.groupId);
			}
			this.httpClient.get(GLOBAL.api + '/GlobalTicket', {headers}).toPromise()
				.then((overview: any) => {
					this.assignedTickets = overview;
					const none = this.assignedTickets['none'];
					delete this.assignedTickets['none'];
					Object.keys(this.assignedTickets).forEach(group => {
						this.assignedTickets[group].type = group.substring(0, group.indexOf('_'));
					});
					if (this.selectedType === 'global') {
						this.unassignedTickets = none;
						Object.keys(this.assignedTickets).forEach(group => {
							const key = 'cdkList-' + group;
							if (this.dropListsIds.indexOf(key) === -1) {
								this.dropListsIds.push(key);
							}
						});
					}
					if (this.movingTicket !== null) {
						let found = false;
						for (const key of Object.keys(overview)) {
							for (const ticket of overview[key].tickets) {
								if (ticket.id === this.movingTicket.id) {
									this.movingTicket = ticket;
									found = true;
									break;
								}
								if (found) {
									break;
								}
							}
						}
					}
					this.loading = false;
				});
		});
	}

	goBack() {
		this.location.back();
	}

	getColumns() {
		if (this.selectedType === 'group') {
			const users = [];
			this.group.users.forEach(user => {
				users.push('user_' + user.id);
			});
			return users;
		} else {
			return Object.keys(this.assignedTickets);
		}
	}

	getColumn(columnIndex) {
		if (this.selectedType === 'group') {
			let foundUser;
			for (const user of this.group.users) {
				if ('user_' + user.id === columnIndex) {
					foundUser = user;
					break;
				}
			}
			return foundUser;
		} else {
			return this.assignedTickets[columnIndex];
		}
	}

	drop(event: CdkDragDrop<any>) {
		if (event.previousContainer.data !== event.container.data) {
			transferArrayItem(event.previousContainer.data,
				event.container.data,
				event.previousIndex,
				0);
			const oldContainer = event.previousContainer.id.replace('cdkList-', '');
			const newContainer = event.container.id.replace('cdkList-', '');

			const fullId = event.item.element.nativeElement.id.replace('cdkItem-', '').split(':');
			const id = parseInt(fullId[1], 10);

			this.move(oldContainer, newContainer, id);
		}
	}

	moveSelf(id) {
		this.move( //
			'unassigned', //
			this.dropListsIds[1].replace('cdkList-', ''), //
			id
		);
	}

	moveGroup(id) {
		getUser(user => {
			// TODO
			// user.groups.forEach(group => {
			// 	this.move('unassigned', 'group_' + group, id);
			// });
		});
	}

	unGroup(group, id) {
		this.move('group_' + group, 'unassigned', id);
	}

	move(oldContainer, newContainer, id) {
		sendSecureHeader(headers => {
			this.loading = true;
			headers = headers.set('Type', 'assignTo');
			this.httpClient.post(GLOBAL.api + '/GlobalTicket', {
				oldContainer, newContainer, id
			}, {headers}).toPromise()
				.then(_ => {
					this.onRefresh();
				});
		});
	}

	isHalting(): boolean {
		return super.isHalting() && this.modalService.hasOpenModals();
	}

	openMove(modal, id: any) {
		this.movingTicket = id;
		this.modalService.open(modal, {
				centered: true
			}
		).result.then(_ => {
			this.movingTicket = null;
		}).catch(_ => {
		});
	}

	filterInObject(row, filter) {
		if (!filter) {
			filter = '';
		}
		filter = filter.toLowerCase();
		for (const data of Object.keys(row)) {
			if (row[data].toString().toLowerCase().includes(filter)) {
				return true;
			}
		}
		return false;
	}

	isFor(major: string, type: string, movingTicket: any, holder: any) {
		type = type.split('_')[0] + 's';
		const assignedList: any[] = movingTicket[major + '_' + type];

		for (const assigned of assignedList) {
			if (assigned.id === holder.id) {
				return true;
			}
		}
		return false;
	}

	fastAssign(value: boolean, type: string, typeId: string, movingTicket: any) {
		type = type.split('_')[0];

		let oldContainer = 'unassigned';
		let newContainer = type + '_' + typeId;

		if (!value) {
			oldContainer = newContainer;
			newContainer = 'unassigned';
		}

		this.move(oldContainer, newContainer, movingTicket.id);
	}
}
