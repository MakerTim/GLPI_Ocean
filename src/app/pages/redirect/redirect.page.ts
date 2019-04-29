// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component} from '@angular/core';

@Component({
	selector: 'notfound',
	templateUrl: './redirect.page.html',
	styleUrls: ['./redirect.page.css']
})
export class RedirectPage {
	public title: string;

	constructor() {
		window.location.href = window.location.href.replace(':4200', '');
	}
}
