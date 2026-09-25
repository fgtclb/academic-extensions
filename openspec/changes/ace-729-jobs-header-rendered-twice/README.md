# ace-729-jobs-header-rendered-twice

The job plugins render the content element header themselves, on top of the
header the content element layout already renders. A per-extension switch
decides who renders it; by default the layout does, once.
