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
			this.submenuItems.push([typesPage, '/'  + pageObj[typesPage]]);
		}
	}

}
