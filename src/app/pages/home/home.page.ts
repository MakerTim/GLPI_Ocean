// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {GLOBAL} from '../../services/global';
import {getUser} from '../../services/login.service';

@Component({
	selector: 'home',
	templateUrl: './home.page.html',
	styleUrls: ['./home.page.scss']
})
export class HomePage implements OnInit {

	@ViewChild('user') userElement: ElementRef;
	@ViewChild('pass') passElement: ElementRef;

	public glpiURL = GLOBAL.glpiUrl;
	public isAdmin = false;
	public groups = [];

	ngOnInit() {
		getUser(user => {
			// tslint:disable-next-line:no-bitwise
			if (user.rights && ((user.rights.ticket) & 2) === 2) {
				this.isAdmin = true;
				if (user.groups) {
					this.groups = user.groups;
				}
			}
		});
	}

	hasMultipleGroups() {
		return this.groups.length > 1;
	}
}
