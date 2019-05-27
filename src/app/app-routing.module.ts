import {NgModule} from '@angular/core';
import {RouterModule, Routes} from '@angular/router';
import {ReportComponent} from './report/report.component';
import {GraphComponent} from './graph/graph.component';

const routes: Routes = [
    {path: 'report', component: ReportComponent},
    {path: 'graph', component: GraphComponent}
];

@NgModule({
    imports: [RouterModule.forRoot(routes)],
    exports: [RouterModule]
})
export class AppRoutingModule {
}
