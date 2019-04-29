import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {RedirectPage} from './redirect.page';

describe('NotFoundComponent', () => {
	let component: RedirectPage;
	let fixture: ComponentFixture<RedirectPage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [RedirectPage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(RedirectPage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
