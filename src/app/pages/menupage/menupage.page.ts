// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from '@angular/router';
import {navItemListener} from '../../services/nav';

@Component({
	selector: 'page',
	templateUrl: './menupage.page.html',
	styleUrls: ['./menupage.page.scss']
})
export class MenupagePage implements OnInit {
	public page = '';
	public submenuItems = [];

	public iconDB = {
		ticketCreate: 'fas fa-headset',
		globalTicket: 'fas fa-align-left',
		dashboard: 'fas fa-columns',
		search: 'fas fa-search',
		computer: 'fas fa-laptop',
		monitor: 'fas fa-desktop',
		networkEquipment: 'fas fa-network-wired',
		peripheral: 'fas fa-mobile-alt',
		phone: 'fas fa-phone-square',
		printer: 'fas fa-print',
		software: 'fas fa-mail-bulk',
		softwareLicense: 'fas fa-credit-card',
		certificate: 'fas fa-lock',
		line: 'fas fa-globe',
		pDU: 'fas fa-calculator',
		rack: 'fas fa-server',
		enclosure: 'fas fa-door-open',
		'ticket-form': 'fas fa-align-center',
		logs: 'fas fa-receipt',
		settings: 'fas fa-cogs',
		users: 'fas fa-users',
	};

	constructor(
		private route: ActivatedRoute) {
	}

	ngOnInit(): void {
		const thiz = this;
		this.route.params.subscribe(page => thiz.setPage(page));
	}

	setPage(page) {
		const thiz = this;
		thiz.page = page.page;

		navItemListener(nav => thiz.onNavFound(nav));
	}

	onNavFound(nav) {
		for (const navPage of Object.keys(nav)) {
			if (navPage === this.page) {
				this.foundPage(nav[navPage], navPage);
			}
		}
	}

	saveI18n(input: string) {
		return input.substr(0, 1).toLocaleLowerCase() + input.substr(1).replace(/\s+/g, '');
	}

	foundPage(pageObj, type) {
		this.submenuItems.length = 0;
		for (let typesPage of Object.keys(pageObj)) {
			if (type === 'plugins') {
				typesPage = 'plugin-' + typesPage.replace(/(^Plugin)|(Config$)/g, '');
			}
			this.submenuItems.push([typesPage, '/' + pageObj[typesPage]]);
		}
	}

	iconOf(icon: string) {
		if (icon in this.iconDB) {
			return this.iconDB[icon];
		}
		console.warn(icon);
		return 'fas fa-paint-brush';
	}

	indexOf(submenuItems: any[], submenuItem: any) {
		return submenuItems.indexOf(submenuItem);
	}
}
