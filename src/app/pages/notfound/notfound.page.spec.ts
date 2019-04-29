import {async, ComponentFixture, TestBed} from '@angular/core/testing';

import {NotfoundPage} from './notfound.page';

describe('NotFoundComponent', () => {
	let component: NotfoundPage;
	let fixture: ComponentFixture<NotfoundPage>;

	beforeEach(async(() => {
		TestBed.configureTestingModule({
			declarations: [NotfoundPage]
		})
			.compileComponents();
	}));

	beforeEach(() => {
		fixture = TestBed.createComponent(NotfoundPage);
		component = fixture.componentInstance;
		fixture.detectChanges();
	});

	it('should create', () => {
		expect(component).toBeTruthy();
	});
});
