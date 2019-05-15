import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {SearchticketPage} from './searchticket.page';

describe('HomeComponent', () => {
	let component: SearchticketPage;
	let fixture: ComponentFixture<SearchticketPage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [SearchticketPage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(SearchticketPage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
