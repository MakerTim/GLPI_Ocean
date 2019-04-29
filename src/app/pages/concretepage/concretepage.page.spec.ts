import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {ConcretePagePage} from './concretepage.page';

describe('HomeComponent', () => {
	let component: ConcretePagePage;
	let fixture: ComponentFixture<ConcretePagePage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [ConcretePagePage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(ConcretePagePage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
