/* tslint:disable:triple-equals */
// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {fields, IdValue, retrieveTicketLayout, TicketCategory, TicketSubCategory} from '../../models/Ticket';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';
import {SnackbarService} from 'ngx-snackbar';
import {I18n} from '../../pipes/translator';
import {RefreshPage} from '../../models/RefreshPage';

@Component({
	selector: 'ticket-admin',
	templateUrl: './ticketadmin.page.html',
	styleUrls: ['./ticketadmin.page.scss']
})
export class TicketAdminPage extends RefreshPage implements OnInit {

	public htmlInput = [
		'button', 'checkbox', 'color', 'date', 'email', 'file', 'hidden',
		'image', 'month', 'number', 'password', 'radio', 'range', 'reset',
		'search', 'submit', 'tel', 'text', 'time', 'url', 'week'
	];

	public inputTypes = [
		'dropdown', 'field', 'input', 'text', 'file', 'pre', 'empty'
	];

	public viewing: string[] = [];
	public editing: string[] = [];
	public databaseStructure: any = {};
	public subtypeCache: any = {};
	public categories: TicketCategory[] = [];
	public page = 1;
	public newOptionName = '';
	public hiddenPreview = false;
	public debug = true;
	public error: string;
	public customField = '';

	constructor(
		private httpClient: HttpClient,
		private modalService: NgbModal,
		private snackbarService: SnackbarService) {
		super(60);
	}

	ngOnInit() {
		super.ngOnInit();
		const thiz = this;
		this.getSettings();
		setTimeout(() => {
			thiz.getDBStructure();
		}, 250);
	}

	onRefresh() {
		this.getSettings();
		this.getDBStructure();
	}

	isHalting(): boolean {
		return super.isHalting() || this.viewing.length > 0 || this.editing.length > 0 || this.modalService.hasOpenModals();
	}

	getSettings(callback = null) {
		const thiz = this;
		retrieveTicketLayout(this.httpClient,
			categories => {
				thiz.categories = categories;
				if (callback) {
					callback();
				}
			},
			console.error);
	}

	getDBStructure() {
		const thiz = this;
		sendSecureHeader((headers: HttpHeaders) => {
			headers = headers.set('Type', 'database');
			this.httpClient.get(GLOBAL.api + '/TicketForm', {headers}).toPromise()
				.then(tables => {
					thiz.databaseStructure = tables;
				})
				.catch(console.error);
		});
	}

	subArray(array: []) {
		const copyArray = [...array];
		copyArray.shift();
		return copyArray;
	}

	objectKeys(obj: any) {
		return Object.keys(obj);
	}

	getPageSymbol(current: number) {
		return this.categories[current - 1].category_i18n;
	}

	isViewing(category: string, type: string) {
		return this.viewing.indexOf(category + '-' + type) >= 0;
	}

	isEditing(category: string, type: string) {
		return this.editing.indexOf(category + '-' + type) >= 0;
	}

	hide(category: string, type: string) {
		const key = category + '-' + type;
		const indexOfEdit = this.editing.indexOf(key);
		const indexOfView = this.viewing.indexOf(key);
		if (indexOfEdit >= 0) {
			this.editing.splice(indexOfEdit, 1);
		}
		if (indexOfView >= 0) {
			this.viewing.splice(indexOfView, 1);
		}
	}

	toggleShowEdit(category: string, type: string) {
		const key = category + '-' + type;
		const indexOfView = this.viewing.indexOf(key);
		const indexOfEdit = this.editing.indexOf(key);

		if (indexOfView >= 0) {
			if (indexOfEdit >= 0) {
				this.editing.splice(indexOfEdit, 1);
			} else {
				this.editing.push(key);
			}
		} else {
			this.viewing.push(key);
		}
	}

	addNewOption() {
		let type = this.newOptionName;
		const selectedCategory = this.categories[this.page - 1];
		this.newOptionName = '';

		if (this.hasCategory(selectedCategory, type)) {
			for (let i = 1; i < 1000; i++) {
				const typeX = type + '-' + i;
				if (!this.hasCategory(selectedCategory, typeX)) {
					type = typeX;
					break;
				}
			}
		}
		if (typeof selectedCategory.data === 'string') {
			return;
		}

		const newObject: any = {};
		newObject[type] = {...fields()};
		selectedCategory.data.push(newObject);
	}

	addOption(screen, options: any[]) {
		const thiz = this;
		this.modalService
			.open(screen, {centered: true})
			.result
			.then((result: string[]) => {
				if (!result || result.length <= 1
					|| !result[0] || result[0].length === 0
					|| !result[1] || result[1].length === 0) {
					thiz.popup('ticket-admin.option-empty');
					return;
				}
				const toAdd = {};
				toAdd[result[0]] = result[1];

				if (options.length === 1) {
					options.push(result[1]);
				}

				options.push(toAdd);
				thiz.popup('ticket-admin.option-added ' + result[0] + ' ' + result[1]);
			});
	}

	deleteOption(options: any[], object: any) {
		let foundObject = null;
		for (const item of options) {
			if (typeof item !== 'object') {
				continue;
			}
			const value = item[Object.keys(item)[0]];
			if (object == value) {
				foundObject = item;
				break;
			}
		}

		const index = options.indexOf(foundObject);
		if (index <= 0) {
			this.popup('ticket-admin.already-empty');
			return;
		}
		options.splice(index, 1);
	}

	deleteAll(options: any[]) {
		options.length = 1;
		options.push(0);
		this.popup('ticket-admin.empty');
	}

	hasCategory(category: TicketCategory, catName: string) {
		if (typeof category.data === 'string') {
			return false;
		}
		for (const subCategories of category.data) {
			if (this.objectKeys(subCategories)[0] === catName) {
				return true;
			}
		}
		return false;
	}

	save() {
		sendSecureHeader(headers => {
			headers = headers.set('Type', 'save');
			this.httpClient.post(GLOBAL.api + '/TicketForm', this.categories[this.page - 1], {headers}).toPromise()
				.then(success => {
					if (success === true) {
						this.popup('ticket-admin.saved');
					} else {
						this.popup('ticket-admin.failed');
					}
				})
				.catch(console.error);
		});
	}

	toJson() {
		const fakeElement = document.createElement('a');
		fakeElement.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(JSON.stringify(this.categories[this.page - 1])));
		fakeElement.setAttribute('download', this.categories[this.page - 1].category_i18n + '.json');

		fakeElement.style.display = 'none';

		document.body.appendChild(fakeElement);
		fakeElement.click();
		document.body.removeChild(fakeElement);
	}

	logAll() {
		window['debug'] = this.categories;
		console.log(this.categories);
		this.popup('ticket-admin.see-log');
	}

	resetAll(btn) {
		btn.disabled = true;
		this.getSettings(() => {
			this.popup('ticket-admin.reset');
		});
	}

	refresh() {
		this.hiddenPreview = true;
		setTimeout(() => this.hiddenPreview = false, 1);
	}

	moveSetting(previousIndex: number, currentIndex: number, selectedCategory: TicketCategory, subCategory: string) {
		if (currentIndex === previousIndex) {
			return;
		}

		const data: any = selectedCategory.data;
		let index = -1;
		for (let i = 0; i < data.length; i++) {
			if (this.objectKeys(data[i])[0] === subCategory) {
				index = i;
				break;
			}
		}

		const keysOrder: string[] = Object.keys(data[index][subCategory]);
		const movedItem = keysOrder.splice(previousIndex, 1)[0];
		keysOrder.splice(currentIndex, 0, movedItem);
		const newObjectFormat = {};
		for (const key of keysOrder) {
			newObjectFormat[key] = '';
		}

		data[index][subCategory] = {...newObjectFormat, ...data[index][subCategory]};
	}

	moveSub(previousIndex: number, currentIndex: number, selectedCategory: TicketSubCategory[]) {
		if (currentIndex === previousIndex) {
			return;
		}

		const movedItem = selectedCategory.splice(previousIndex, 1)[0];
		selectedCategory.splice(currentIndex, 0, movedItem);
	}

	indexOf(list, element) {
		return list.indexOf(element);
	}

	openPopup(selectedCategory: TicketCategory, data, mainCategory) {
		this.halt = true;
		this.modalService
			.open(mainCategory, {
				centered: true,
				// @ts-ignore
				catName: 'test'
			})
			.result
			.then((result) => {
				if (!result || result.length === 0) {
					this.popup('ticket-admin.catname-empty');
					return;
				}
				const catData: any = selectedCategory.data;
				const index = catData.indexOf(data);
				const newObject = {};
				newObject[result] = catData[index][Object.keys(catData[index])[0]];
				catData[index] = newObject;
				this.popup('ticket-admin.catname-updated');
				this.popup('ticket-admin.forget-save');
			})
			.catch(() => {
			});
	}

	popup(msg: string) {
		msg = I18n.resolvePlus(msg);
		this.snackbarService.add({
			msg,
			timeout: 7500,
			action: {
				text: '✘'
			}
		});
	}

	newCategory(screen) {
		const thiz = this;
		this.modalService
			.open(screen, {centered: true})
			.result
			.then((result: string) => {
				if (!result || result.length === 0) {
					thiz.popup('ticket-admin.catname-empty');
					return;
				}
				sendSecureHeader(headers => {
					headers = headers.set('Type', 'create');
					this.httpClient.post(GLOBAL.api + '/TicketForm', result, {headers}).toPromise()
						.then(success => {
							if (typeof success === 'number' && success >= 1) {
								thiz.popup('ticket-admin.catname-created');
								thiz.popup('ticket-admin.save');

								thiz.categories.splice(0, 0, {id: success, category_i18n: result, data: []});
							} else {
								this.popup('ticket-admin.failed');
							}
						})
						.catch(error => {
							this.popup('ticket-admin.failed');
							console.error(error);
						});
				});
			})
			.catch(() => {
			});
	}

	changeOrderCategory(previousIndex: number, currentIndex: number) {
		if (previousIndex === currentIndex) {
			return;
		}
		const id1 = this.categories[previousIndex].id.toString();
		const id2 = this.categories[currentIndex].id.toString();

		const selectedPage = this.categories[this.page - 1];
		const movedItem = this.categories.splice(previousIndex, 1)[0];
		this.categories.splice(currentIndex, 0, movedItem);
		this.page = this.indexOf(this.categories, selectedPage) + 1;

		sendSecureHeader(headers => {
			headers = headers.set('Type', 'switch')
				.set('ID1', id1)
				.set('ID2', id2);
			this.httpClient.get(GLOBAL.api + '/TicketForm', {headers}).toPromise()
				.then(success => {
					this.onRefresh();
					this.page = this.indexOf(this.categories, selectedPage) + 1;
				})
				.catch(error => {
					this.popup('ticket-admin.failed');
					console.error(error);
					this.onRefresh();
				});
		});
	}

	fixName(input: string) {
		return input.replace('ticket.', '').replace('custom.', '');
	}

	orderCat(screen) {
		this.modalService
			.open(screen, {centered: true});
	}

	copyOption(category_i18n: string, option: TicketSubCategory) {
		const selectedCategory = this.categories[this.page - 1];
		const data = selectedCategory.data;
		if (typeof data === 'string') {
			console.error('failed to copy option');
			return;
		} else {
			const copyOfOption = JSON.parse(JSON.stringify(option));
			const optionName = this.objectKeys(copyOfOption)[0];

			copyOfOption[optionName + '_'] = copyOfOption[optionName];
			delete copyOfOption[optionName];

			data.push(copyOfOption);
		}
		this.halt = true;

		console.log(category_i18n);
		console.log(option);
		console.log(this);
	}

	addCustomField(data: any) {
		data['custom.' + this.customField] = ['empty'];
	}

	findSubtypes(dataOptionElement: any) {
		if (this.subtypeCache[dataOptionElement]) {
			return this.subtypeCache[dataOptionElement];
		}
		sendSecureHeader(headers => {
			headers = headers.set('Type', 'options')
				.set('Table', dataOptionElement)
				.set('Field', 'name');
			this.httpClient.get<IdValue[]>(GLOBAL.api + '/TicketForm', {headers}).toPromise()
				.then(values => {
					this.subtypeCache[dataOptionElement] = values;
				})
				.catch(console.error);
			this.subtypeCache[dataOptionElement] = [];
		});
		return [];
	}

	hasTypes(dataOptionElement: string) {
		if (!dataOptionElement) {
			return false;
		}
		const keys = this.objectKeys(this.databaseStructure);
		return keys.indexOf(dataOptionElement.substr(0, dataOptionElement.length - 1) + 'types') >= 0;
	}
}
