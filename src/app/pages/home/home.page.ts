// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, ElementRef, ViewChild} from '@angular/core';
import {GLOBAL} from '../../services/global';

@Component({
	selector: 'home',
	templateUrl: './home.page.html',
	styleUrls: ['./home.page.scss']
})
export class HomePage {

	@ViewChild('user') userElement: ElementRef;
	@ViewChild('pass') passElement: ElementRef;

	public glpiURL = GLOBAL.glpiUrl;

}
