import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {UserPage} from './user.page';

describe('HomeComponent', () => {
	let component: UserPage;
	let fixture: ComponentFixture<UserPage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [UserPage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(UserPage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
