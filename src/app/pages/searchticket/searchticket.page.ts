// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {RefreshPage} from '../../models/RefreshPage';
import {sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {Ticket} from '../../models/Ticket';
import {ActivatedRoute, Router} from '@angular/router';

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

	public selectedFields: any = {
		'name': this.fields[0],
		'content': this.fields[1],
		'date_mod': this.fields[2],
		'date_creation': this.fields[3],
	};

	public selectedFilters = {};
	public today = new Date().toISOString().split('T')[0];

	public lastRng;
	public lastSearch = '';
	public foundTickets: Ticket[] = [];
	public isSearching = false;

	constructor(
		private httpClient: HttpClient,
		private activatedRoute: ActivatedRoute,
		private router: Router) {
		super(60);
	}

	ngOnInit() {
		super.ngOnInit();
		const params = this.activatedRoute.snapshot.queryParams;
		if ('fields' in params) {
			this.selectedFields = this.getFieldsFromString(params.fields);
		}
		if ('filters' in params) {
			this.selectedFilters = this.getFiltersFromString(params.filters);
		}
		if ('search' in params) {
			this.search(params.search);
		}
	}

	onRefresh() {
		// this.search();
	}

	getFilterString() {
		const indexOfA = 'A'.charCodeAt(0);
		const indexKeys = [];

		Object.keys(this.selectedFilters).forEach(filter => {
			let index;
			for (let i = 0; i < this.filters.length; i++) {
				if (this.fieldsName(this.filters[i]) === filter) {
					index = indexOfA + i;
					break;
				}
			}
			if (!index) {
				return;
			}

			const times = this.selectedFilters[filter];
			indexKeys.push(String.fromCharCode(index) + new Date(times[0]).getTime() + '-' + new Date(times[1]).getTime());
		});

		return indexKeys.join(',');
	}

	getFiltersFromString(stringIn: string) {
		const indexOfA = 'A'.charCodeAt(0);
		const filters = {};
		for (let charsIn of stringIn.split(',')) {
			if (!charsIn) {
				break;
			}
			const filter = this.filters[charsIn.charCodeAt(0) - indexOfA];
			charsIn = charsIn.substring(1);
			const times: any[] = charsIn.split('-');
			const filterName = this.fieldsName(filter);
			for (let i = 0; i < times.length; i++) {
				if (times[i] === 'NaN') {
					times[i] = new Date();
				} else {
					times[i] = new Date(parseInt(times[i], 10));
				}
				times[i] = times[i].toISOString().split('T')[0];
			}
			filters[filterName] = times;
		}
		return filters;
	}

	getFieldsFromString(charsIn: string) {
		const indexOfA = 'A'.charCodeAt(0);
		const obj = {};
		charsIn.split('').forEach(char => {
			const field = this.fields[char.charCodeAt(0) - indexOfA];
			const fieldName = this.fieldsName(field);
			obj[fieldName] = field;
		});
		return obj;
	}

	getFieldsString() {
		const indexOfA = 'A'.charCodeAt(0);
		let fieldsString = '';
		Object.values(this.selectedFields).forEach(field => {
			// @ts-ignore
			fieldsString += String.fromCharCode(indexOfA + this.fields.indexOf(field));
		});
		return fieldsString;
	}

	search(searchString = this.lastSearch) {
		this.lastSearch = searchString;
		this.lastRng = Math.random();
		this.isSearching = true;
		const fieldsString = '';
		this.router.navigate([], {
			relativeTo: this.activatedRoute,
			queryParams: {
				fields: this.getFieldsString(),
				filters: this.getFilterString(),
				search: searchString
			},
			queryParamsHandling: 'merge'
		});
		sendSecureHeader(headers => {
			headers = headers.set('rng', this.lastRng);
			this.httpClient.put<[string, Ticket[]]>(GLOBAL.api + '/Search',
				[searchString, this.selectedFields, this.selectedFilters], {headers}).toPromise()
				.then(response => {
					if (response[0] === this.lastRng.toString()) {
						if (!Array.isArray(response[1])) {
							this.foundTickets = <Ticket[]>Object.values(response[1]);
						} else {
							this.foundTickets = response[1];
						}
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
