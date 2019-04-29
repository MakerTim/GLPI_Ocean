import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TicketInputComponent } from './ticket-input.component';

describe('TicketBlockComponent', () => {
  let component: TicketInputComponent;
  let fixture: ComponentFixture<TicketInputComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TicketInputComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TicketInputComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
