// tslint:disable:component-selector
// tslint:disable:component-class-suffix
import {Component, OnInit} from '@angular/core';
import {Location} from '@angular/common';
import {HttpClient} from '@angular/common/http';
import {ActivatedRoute} from '@angular/router';
import {getUserRaw, sendSecureHeader} from '../../services/login.service';
import {GLOBAL} from '../../services/global';
import {Ticket} from 'src/app/models/Ticket';
import {closeTicket, followup, readInput, sendAttachments} from '../../services/ticket.service';
import {NgbModal} from '@ng-bootstrap/ng-bootstrap';

@Component({
	selector: 'empty',
	templateUrl: './empty.page.html',
	styleUrls: ['./empty.page.scss']
})

export class EmptyPage implements OnInit {

	constructor(
		private httpClient: HttpClient,
		private route: ActivatedRoute) {
	}

	ngOnInit() {
	}
}
