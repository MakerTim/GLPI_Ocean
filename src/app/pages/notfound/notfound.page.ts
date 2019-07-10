// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnDestroy, OnInit} from '@angular/core';
import {RefreshPage} from '../../models/RefreshPage';

@Component({
	selector: 'notfound',
	templateUrl: './notfound.page.html',
	styleUrls: ['./notfound.page.css']
})
export class NotfoundPage extends RefreshPage implements OnInit, OnDestroy {
	public title: string;

	constructor() {
		super(30);
	}

	onRefresh() {
		window.location.reload(true);
	}

	ngOnInit() {
		super.ngOnInit();
		document.getElementsByTagName('body')[0].style.overflow = 'none';
		throw new Error(window.location.href);
	}

	ngOnDestroy() {
		super.ngOnDestroy();
		document.getElementsByTagName('body')[0].style.overflow = '';
	}

}
