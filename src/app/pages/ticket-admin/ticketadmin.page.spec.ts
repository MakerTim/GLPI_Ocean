import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {TicketadminPage} from './ticketadmin.page';

describe('HomeComponent', () => {
	let component: TicketadminPage;
	let fixture: ComponentFixture<TicketadminPage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [TicketadminPage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(TicketadminPage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
