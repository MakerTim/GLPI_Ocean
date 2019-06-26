// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {getUser, sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {Router} from '@angular/router';

@Component({
	selector: 'page',
	templateUrl: './dashboardmenu.page.html',
	styleUrls: ['./dashboardmenu.page.scss']
})
export class DashboardMenuPage implements OnInit {

	public submenuItems = [];

	constructor(
		private httpClient: HttpClient,
		private router: Router) {
	}

	ngOnInit() {
		getUser(user => {
			// TODO
			// if (user.groups.length === 1) {
			// 			// 	this.router.navigateByUrl('dashboard/Ticket/' + user.groups[0]);
			// 			// } else {
			// 			// 	user.groups.forEach(groupId => {
			// 			// 		const index = this.submenuItems.push([groupId, groupId]);
			// 			//
			// 			// 		this.getGroup(index, groupId);
			// 			// 	});
			// 			// }
		});
	}

	getGroup(index, groupId) {
		sendSecureHeader(headers => {
			headers = headers
				.set('id', groupId.toString());
			this.httpClient.get(GLOBAL.api + '/group', {headers}).toPromise()
				.then((group: any) => {
					this.submenuItems[index - 1][0] = group.name;
				});
		});
	}
}
