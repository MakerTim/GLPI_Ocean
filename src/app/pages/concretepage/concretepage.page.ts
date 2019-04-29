// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {ActivatedRoute} from '@angular/router';
import {getURLParam} from '../../services/mappingUtil';

@Component({
	selector: 'home',
	templateUrl: './concretepage.page.html',
	styleUrls: ['./concretepage.page.scss']
})

export class ConcretePagePage implements OnInit {

	public page: string;
	public from: string;

	constructor(
		private httpClient: HttpClient,
		private route: ActivatedRoute) {
	}

	ngOnInit() {
		const thiz = this;
		this.route.params.subscribe(page => thiz.setPage(page));
	}

	setPage(page) {
		this.page = page.page;
		this.from = getURLParam('FullName');
		if (this.from) {
			this.from = atob(this.from);
		}
	}

}
