// Sample data structure
const samples = [
    { SampleID: 1, Subcontractor: 'Subcontractor A' },
    { SampleID: 2, Subcontractor: 'Subcontractor B' },
    { SampleID: 3, Subcontractor: 'Subcontractor A' },
    { SampleID: 4, Subcontractor: 'Subcontractor C' },
    // Add more samples as needed
];

// Step 1: Group samples by subcontractor
const groupedSamples = samples.reduce((acc, sample) => {
    const { Subcontractor } = sample;
    if (!acc[Subcontractor]) {
        acc[Subcontractor] = [];
    }
    acc[Subcontractor].push(sample);
    return acc;
}, {});

// Step 2: Populate the sample list
const sampleList = document.getElementById('sampleList'); // Adjust this to your actual sample list element

for (const subcontractor in groupedSamples) {
    // Create a section for each subcontractor
    const subcontractorHeader = document.createElement('h4');
    subcontractorHeader.textContent = subcontractor;
    sampleList.appendChild(subcontractorHeader);

    // Create buttons for each sample under the subcontractor
    groupedSamples[subcontractor].forEach(sample => {
        const button = document.createElement('button');
        button.className = 'btn btn-outline-primary btn-block mb-2 sample-btn';
        button.setAttribute('data-sampleid', sample.SampleID);
        button.textContent = sample.SampleID;
        sampleList.appendChild(button);
    });
}
