// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {RefreshPage} from '../../models/RefreshPage';
import {User} from '../../models/User';
import {GLOBAL} from '../../services/global';
import {sendSecureHeader} from '../../services/login.service';
import {ActivatedRoute} from '@angular/router';
import {Location} from '@angular/common';

@Component({
	selector: 'logs',
	templateUrl: './user.page.html',
	styleUrls: ['./user.page.scss']
})

export class UserPage extends RefreshPage implements OnInit {

	public id: string;
	public user: User;
	public kaceURL = GLOBAL.kaceUrl;
	public fields = ['id', 'name', 'firstname', 'realname', '', 'date_creation', 'date_mod', ''];

	constructor(
		private httpClient: HttpClient,
		private location: Location,
		private router: ActivatedRoute) {
		super(60);
	}

	goBack() {
		this.location.back();
	}

	ngOnInit() {
		super.ngOnInit();
		this.router.params.subscribe(param => {
			this.id = param.id;
			this.retrieveUser();
		});
	}

	onRefresh() {
		this.retrieveUser();
	}

	contains(x, y) {
		return x.indexOf(y) >= 0;
	}

	subList(lst, amnt) {
		if (lst.length > amnt) {
			return lst.slice(0, amnt);
		}
		return lst;
	}

	retrieveUser() {
		sendSecureHeader(headers => {
			headers = headers.set('id', this.id);
			this.httpClient.get<User>(GLOBAL.api + '/User', {headers}).toPromise()
				.then(user => {
						this.user = user;
					}
				);
		});
	}
}

