// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {ActivatedRoute} from '@angular/router';
import {GLOBAL} from '../../services/global';
import {sendSecureHeader} from '../../services/login.service';
import {getDifferences, getFromStorage, setToStorage} from '../../services/mappingUtil';
import {RefreshPage} from '../../models/RefreshPage';

declare var $: any;

@Component({
	selector: 'listall',
	templateUrl: './listall.page.html',
	styleUrls: ['./listall.page.scss']
})

export class ListallPage extends RefreshPage implements OnInit {

	static defaultParameters = [
		'name',
		'entities_id',
		'states_id',
		'locations_id',
		'date_mod'
	];

	public page: string;
	public filter: string[];
	public items: any[] = [];
	public properties: string[];
	public allProperties: string[];
	public loading = true;
	public pageNr = 1;
	public limit = 50;

	public searches: { field: string, search: string, ignoreCase: boolean }[] = [
		{field: 'name', search: '', ignoreCase: true}
	];

	@ViewChild('filterElement') filterElement: ElementRef;

	constructor(
		private httpClient: HttpClient,
		private route: ActivatedRoute) {
		super(15);
	}

	ngOnInit() {
		super.ngOnInit();
		const thiz = this;
		this.route.params.subscribe(page => thiz.setPage(page));
	}

	onRefresh() {
		if ($('.dropdown-menu.show').length >= 2) {
			return;
		}
		this.loadTable();
	}

	onLoadTable(results: any) {
		const thiz = this;
		if (results && results.length > 0) {
			this.allProperties = Object.keys(results[0]);
			if (this.properties.length === 0) {
				for (const defaultParameter of ListallPage.defaultParameters) {
					if (this.allProperties.includes(defaultParameter)) {
						this.changeProperty(defaultParameter);
					}
				}
			}
		}
		this.items.length = 0;
		for (const result of results) {
			this.items.push(result);
		}

		if (results && results.length > 0) {
			$(this.filterElement.nativeElement).selectpicker().on('change', function () {
				const differences = getDifferences<string>(thiz.properties, $(this).val());
				for (const newItem of differences[0]) {
					thiz.changeProperty(newItem);
				}
				for (const removedItem of differences[1]) {
					thiz.changeProperty(removedItem, false);
				}
			});
		}
	}

	getItems() {
		let hasFilter = false;

		for (const search of this.searches) {
			if (search.field && (search.search || !search.ignoreCase)) {
				hasFilter = true;
				break;
			}
		}

		if (hasFilter) {
			return this.items.filter(item => {
				let matches = true;
				for (const search of this.searches) {
					if (search.field && (search.search || !search.ignoreCase)) {
						const field = item[search.field];
						if (field === undefined || field == null) {
							matches = search.search === '' && !search.ignoreCase;
							continue;
						}
						if (search.ignoreCase) {
							if (field.toLocaleLowerCase().indexOf(search.search.toLocaleLowerCase()) < 0) {
								matches = false;
							}
						} else {
							if (search.search === '') {
								if (field) {
									matches = false;
								}
							} else {
								if (field.indexOf(search.search) < 0) {
									matches = false;
								}
							}
						}
					}
				}
				return matches;
			});
		}
		return this.items;
	}

	isSelectedProperties(property: string) {
		return this.properties.includes(property);
	}

	changeProperty(param: string, add = true) {
		if (add) {
			this.properties.push(param);
		} else {
			this.properties.splice(this.properties.indexOf(param), 1);
		}
		setToStorage('glpi-property-list-' + this.page, JSON.stringify(this.properties));
	}

	hasProperty(key: string) {
		return this.properties.includes(key);
	}

	checkForBoolean(property: string, value: string) {
		return property.indexOf('have') !== -1 ||
			property.indexOf('has') !== -1 ||
			property.indexOf('is') !== -1;
	}

	urlOfItem(id: number) {
		return GLOBAL.glpiUrl + 'front/' + this.page.toLowerCase() + '.form.php?id=' + id;
	}

	setPage(page) {
		this.page = page.page;
		this.filter = JSON.parse(getFromStorage('glpi-filter-list-' + this.page, '[]'));
		this.properties = JSON.parse(getFromStorage('glpi-property-list-' + this.page, '[]'));
		this.loadTable();
	}

	loadTable(firstTry: boolean = true) {
		const thiz = this;
		sendSecureHeader((headers: HttpHeaders) => {
			headers = headers
				.set('Type', thiz.page)
				.set('Limit', thiz.limit.toString())
				.set('Page', thiz.pageNr.toString());
			const promise = this.httpClient.get(GLOBAL.api + '/GetAllItems', {headers})
				.toPromise();
			promise.then(result => {
				thiz.loading = false;
				thiz.onLoadTable(result);
			}).catch(error => {
				if (firstTry) {
					this.loadTable(false);
				} else {
					thiz.loading = false;
					console.error('Loading of table failed:', error);
				}
			});
		});
	}

	saveI18n(input: string) {
		return input.substr(0, 1).toLocaleLowerCase() + input.substr(1).replace(/\s+/g, '');
	}

	addSearch() {
		this.searches.push({field: '', search: '', ignoreCase: true});
	}

	remove(searching: any, searches: any[]) {
		searches.splice(searches.indexOf(searching), 1);
	}
}
