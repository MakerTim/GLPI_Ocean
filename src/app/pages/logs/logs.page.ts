// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {Log, SystemEvent} from '../../models/Log';
import {RefreshPage} from '../../models/RefreshPage';

@Component({
	selector: 'logs',
	templateUrl: './logs.page.html',
	styleUrls: ['./logs.page.scss']
})

export class LogsPage extends RefreshPage implements OnInit {

	public error = 'Loading...';
	public publicLogs: Log[] = [];
	public logs: Log[];
	public publicEvents: SystemEvent[] = [];
	public events: SystemEvent[];
	public publicLogsfiles: any[] = [];
	public logsfiles: any[];
	public type: 'log' | 'events' | 'file';
	public subtype: 'cron' | 'event' | 'php-errors' | 'mail' | 'sql-errors';

	constructor(
		private httpClient: HttpClient) {
		super(60);
	}

	ngOnInit() {
		super.ngOnInit();
		this.switchType('log');
		this.onRefresh();
	}

	onRefresh() {
		this.loadLogs(false);
	}

	switchType(type: 'log' | 'events' | 'file') {
		const thiz = this;
		this.type = type;

		this.loadLogs(true);
	}

	switchSubType(subtype: 'cron' | 'event' | 'php-errors' | 'mail' | 'sql-errors') {
		const thiz = this;
		this.subtype = subtype;

		this.loadLogs();
	}

	loadLogs(renewList = true) {
		const thiz = this;

		sendSecureHeader((headers: HttpHeaders) => {
			headers = headers.set('type', thiz.type);
			if (thiz.subtype != null) {
				headers = headers.set('subtype', thiz.subtype);
			}
			this.httpClient.get(GLOBAL.api + '/Logs', {headers}).toPromise()
				.then(list => {
					let pubList;
					if (thiz.type === 'log') {
						// @ts-ignore
						thiz.logs = list;
						pubList = thiz.publicLogs;
					} else if (thiz.type === 'file') {
						// @ts-ignore
						thiz.logsfiles = list.reverse();
						pubList = thiz.publicLogsfiles;
					} else {
						// @ts-ignore
						thiz.events = list;
						pubList = thiz.publicEvents;
					}
					if (renewList) {
						pubList.length = 0;
						thiz.load(10);
					} else {
						const length = pubList.length;
						pubList.length = 0;
						thiz.load(length);
					}
				})
				.catch(err => thiz.error = err.error);
		});
	}

	eventUrl(event: SystemEvent) {
		if (event.type === 'system') {
			return '';
		}

		let type = event.type.toLowerCase();
		if (type.endsWith('s')) {
			type = type.substr(0, event.type.length - 1);
		}
		return this.glpiURL()
			+ 'front/' + type
			+ '.form.php?id=' + event.items_id;
	}

	glpiURL() {
		return GLOBAL.glpiUrl;
	}

	getUserId(log: Log) {
		return Log.getUserId(log);
	}

	getUserName(log: Log) {
		return Log.getUserName(log);
	}

	replaceComma(input: string) {
		return input ? input.replace(/,/g, ', ') : '';
	}

	hasUserId(input: string) {
		return input.match(/":"\d+/) != null || input.match(/\[\d+/);
	}

	parseUserId(input: string) {
		if (input.match(/":"\d+/)) {
			return input.substr(input.indexOf('"', 6) + 3, input.indexOf('@') - input.indexOf('"', 6) - 3);
		} else {
			return input.substr(1, input.indexOf('@') - 1);
		}
	}

	load(amount) {
		if (this.type === 'log') {
			if (this.logs.length === this.publicLogs.length) {
				return;
			}
			for (const log of this.logs.slice(this.publicLogs.length, this.publicLogs.length + amount)) {
				this.publicLogs.push(log);
			}
		} else if (this.type === 'file') {
			if (this.logsfiles.length === this.publicLogsfiles.length) {
				return;
			}
			for (const logfile of this.logsfiles.slice(this.publicLogsfiles.length, this.publicLogsfiles.length + amount)) {
				this.publicLogsfiles.push(logfile);
			}
		} else {
			if (this.events.length === this.publicEvents.length) {
				return;
			}
			for (const event of this.events.slice(this.publicEvents.length, this.publicEvents.length + amount)) {
				this.publicEvents.push(event);
			}
		}
	}

	onScroll() {
		if (window.scrollY + window.innerHeight + window.innerHeight >= document.body.scrollHeight) {
			this.load(50);
		}
	}
}

