// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {Setting} from '../../models/Setting';
import {SwitchComponent} from '../../components/switch/switch.component';
import {RefreshPage} from '../../models/RefreshPage';

@Component({
	selector: 'settings',
	templateUrl: './settings.page.html',
	styleUrls: ['./settings.page.scss']
})

export class SettingsPage extends RefreshPage implements OnInit {

	private static http;

	public error = 'Nothing to see here';
	public settings: Setting[] = [];

	constructor(
		public httpClient: HttpClient) {
		super(60);
		SettingsPage.http = httpClient;
	}

	ngOnInit() {
		super.ngOnInit();
		this.onRefresh();
	}

	onRefresh() {
		this.loadSettings();
	}

	loadSettings() {
		const thiz = this;
		sendSecureHeader((headers: HttpHeaders) => {
			headers = headers.set('setting', 'list');
			thiz.httpClient.get<Setting[]>(GLOBAL.api + '/Settings', {headers}).toPromise()
				.then(settingList => {
					thiz.settings = settingList;
					thiz.error = '';
				})
				.catch(err => thiz.error = err.error);
		});
	}

	onChangeSwitch(valueB: boolean, element: Element) {
		// @ts-ignore
		const thiz: SwitchComponent = this;
		const valueI = valueB ? 1 : 0;
		const id = element.parentElement.getAttribute('followId');

		sendSecureHeader((headers: HttpHeaders) => {
			headers = headers.set('setting', 'setItem')
				.set('id', id)
				.set('value', valueI.toString());

			SettingsPage.http.get(GLOBAL.api + '/Settings', {headers}).toPromise()
				.then(response => {
					if (response !== true) {
						thiz.checked = !valueB;
						thiz.clss = 'errorBorder';
					} else {
						thiz.clss = '';
					}
				})
				.catch(err => {
					thiz.checked = !valueB;
					thiz.clss = 'errorBorder';
				});
		});
	}

	importGroups(btn) {
		btn.target.disabled = true;
		sendSecureHeader((headers: HttpHeaders) => {
			this.httpClient.get(GLOBAL.api + '/ReverseGroup', {headers, responseType: 'blob'})
				.subscribe(log => {
					const blob = new Blob([log], {type: 'application/json'});
					window.open(URL.createObjectURL(blob));
				});
		});
	}
}

