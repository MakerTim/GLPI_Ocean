// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {RefreshPage} from '../../models/RefreshPage';
import {sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {Ticket} from '../../models/Ticket';

@Component({
	selector: 'searchticket',
	templateUrl: './searchticket.page.html',
	styleUrls: ['./searchticket.page.scss']
})

export class SearchticketPage extends RefreshPage implements OnInit {

	public fields = [
		'name',
		'content',
		['date_mod', 'self', 'date'],
		['date_creation', 'self', 'date'],
		['users_id_lastupdater', 'join', 'users#name,realname,firstname'],
		['users_id_recipient', 'join', 'users#name,realname,firstname'],
		['assigned', 'multiple-link', 'users#name,realname,firstname;tickets_users;type,2', 'users_id;tickets_id'],
		['assigned-group', 'multiple-link', 'groups#name,comment;groups_tickets;type,2', 'groups_id;tickets_id'],
		['followed', 'multiple-link', 'users#name,realname,firstname;tickets_users;type,1', 'users_id;tickets_id'],
		['followed-group', 'multiple-link', 'groups#name,comment;groups_tickets;type,1', 'groups_id;tickets_id'],
		['comment', 'multiple-direct', 'itilfollowups#content;itemtype,Ticket', 'items_id'],
		['solution', 'multiple-direct', 'itilsolutions#content;itemtype,Ticket', 'items_id'],
	];

	public filters = [
		['date_mod', 'date'],
		['date_creation', 'date'],
		['closedate', 'date'],
	];

	public selectedFields = {
		'name': this.fields[0],
		'content': this.fields[1],
		'date_mod': this.fields[2],
		'date_creation': this.fields[3],
	};

	public selectedFilters = {};
	public today = new Date().toISOString().split('T')[0];

	public lastRng;
	public lastSearch;
	public foundTickets: Ticket[] = [];
	public isSearching = false;

	constructor(
		private httpClient: HttpClient) {
		super(60);
	}

	ngOnInit() {
		super.ngOnInit();
	}

	onRefresh() {

	}

	search(searchString = this.lastSearch) {
		this.lastSearch = searchString;
		this.lastRng = Math.random();
		this.isSearching = true;
		sendSecureHeader(headers => {
			headers = headers.set('rng', this.lastRng);
			this.httpClient.put<[string, Ticket[]]>(GLOBAL.api + '/Search',
				[searchString, this.selectedFields, this.selectedFilters], {headers}).toPromise()
				.then(response => {
					if (response[0] === this.lastRng.toString()) {
						this.foundTickets = response[1];
						this.isSearching = false;
					}
				});
		});
	}

	isSelectedField(name) {
		return name in this.selectedFields;
	}

	isSelectedFilter(name) {
		return name in this.selectedFilters;
	}

	isValidFilter(name) {
		const filterStart: HTMLInputElement = <HTMLInputElement>document.getElementById('filter-s-' + name);
		const filterEnd: HTMLInputElement = <HTMLInputElement>document.getElementById('filter-e-' + name);

		if (!filterStart || !filterEnd) {
			return false;
		}
		return filterStart.value && filterEnd.value &&
			new Date(filterStart.value).getTime() <= new Date(filterEnd.value).getTime();
	}

	fieldsType(field) {
		if (typeof field === 'string') {
			return 'self';
		}
		return field[1];
	}

	fieldsName(field) {
		if (typeof field === 'string') {
			return field;
		}
		return field[0];
	}

	onFieldChange(element, field) {
		const fieldName = this.fieldsName(field);
		if (element.checked) {
			this.selectedFields[fieldName] = field;
		} else {
			delete this.selectedFields[fieldName];
		}
		this.search();
	}

	updateFilter(field) {
		const fieldName = this.fieldsName(field);

		if (this.isValidFilter(fieldName)) {
			const filterStart: HTMLInputElement = <HTMLInputElement>document.getElementById('filter-s-' + fieldName);
			const filterEnd: HTMLInputElement = <HTMLInputElement>document.getElementById('filter-e-' + fieldName);
			this.selectedFilters[fieldName] = [filterStart.value, filterEnd.value];
		} else {
			this.selectedFilters[fieldName] = [];
		}
		this.search();
	}

	onFilterChange(element, field) {
		const fieldName = this.fieldsName(field);
		if (element.checked) {
			this.updateFilter(field);
		} else {
			delete this.selectedFilters[fieldName];
			this.search();
		}
	}
}
