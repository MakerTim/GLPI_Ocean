import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {TicketPage} from './ticket.page';

describe('HomeComponent', () => {
	let component: TicketPage;
	let fixture: ComponentFixture<TicketPage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [TicketPage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(TicketPage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
