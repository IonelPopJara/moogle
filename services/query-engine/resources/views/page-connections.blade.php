<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Graph View</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="mask-icon" type="image/svg+xml" href="{{asset('favicon.svg')}}" color="#000000">
    <style>
        html {
            color: black;
        }

        #cy {
            width: 100%;
            height: 100vh;
            display: block;
            background: #1f2335;
        }

        .legend {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 5px;
            color: white;
            font-family: Arial, sans-serif;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 50%;
        }
    </style>
</head>

<body>
    <div id="cy"></div>
    <div class="legend">
        <h3>Legend</h3>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #ff757f;"></div>
            <div>Current Page</div>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #7dcfff;"></div>
            <div>Outgoing Link</div>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #c3e88d;"></div>
            <div>Incoming Link</div>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #957FB8;"></div>
            <div>Bidirectional Link</div>
        </div>
    </div>
    <script src="https://unpkg.com/cytoscape/dist/cytoscape.min.js"></script>
    <script>
        const url = "{{ $url }}";
        const title = "{{ $title }}";
        console.log(title);
        const outlinks = {!! json_encode($outlinks) !!};
        const backlinks = {!! json_encode($backlinks) !!};
        const nodesMap = new Map();
        const edges = [];

        // Helper function to extract titles or use URLs as fallback
        function getTitleFromUrl(urlStr, list) {
            const item = list.find(l => l.url === urlStr);
            return item && item.title ? item.title : urlStr;
        }

        // Add the main node
        nodesMap.set(url, {
            data: {
                id: url,
                label: title,
                nodeType: 'main'
            }
        });

        const outlinkSet = new Set(outlinks.map(l => l.url));
        const backlinkSet = new Set(backlinks.map(l => l.url));
        const allLinks = new Set([...outlinkSet, ...backlinkSet]);

        // Create nodes for all links
        for (const link of allLinks) {
            let nodeType = '';

            if (outlinkSet.has(link) && backlinkSet.has(link)) {
                nodeType = 'both';
            } else if (outlinkSet.has(link)) {
                nodeType = 'outlink';
            } else if (backlinkSet.has(link)) {
                nodeType = 'backlink';
            }

            // Find title in outlinks or backlinks
            const title = getTitleFromUrl(link, outlinks) ||
                getTitleFromUrl(link, backlinks) ||
                link;

            nodesMap.set(link, {
                data: {
                    id: link,
                    label: title,
                    nodeType: nodeType
                }
            });
        }

        // Add edges with edge type data for styling
        for (const outlink of outlinks) {
            edges.push({
                data: {
                    source: url,
                    target: outlink.url,
                    edgeType: 'outgoing'
                }
            });
        }

        for (const backlink of backlinks) {
            edges.push({
                data: {
                    source: backlink.url,
                    target: url,
                    edgeType: 'incoming'
                }
            });
        }

        const cy = cytoscape({
            container: document.getElementById('cy'),
            elements: [
                ...Array.from(nodesMap.values()),
                ...edges
            ],
            style: [{
                    selector: 'node',
                    style: {
                        'label': 'data(label)',
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'color': '#fff',
                        'font-size': 14,
                        'text-wrap': 'ellipsis',
                        'text-max-width': '80px',
                        'width': 120,
                        'height': 120,
                        'border-width': 2,
                        'border-color': '#222',
                        'text-outline-color': '#000',
                        'text-outline-width': 2
                    }
                },
                {
                    selector: 'node[nodeType="main"]',
                    style: {
                        'background-color': '#ff757f'
                    }
                },
                {
                    selector: 'node[nodeType="outlink"]',
                    style: {
                        'background-color': '#7dcfff'
                    }
                },
                {
                    selector: 'node[nodeType="backlink"]',
                    style: {
                        'background-color': '#c3e88d'
                    }
                },
                {
                    selector: 'node[nodeType="both"]',
                    style: {
                        'background-color': '#957FB8'
                    }
                },
                {
                    selector: 'edge',
                    style: {
                        'width': 2,
                        'curve-style': 'bezier',
                        'target-arrow-shape': 'triangle',
                        'arrow-scale': 1.5
                    }
                },
                {
                    selector: 'edge[edgeType="outgoing"]',
                    style: {
                        'line-color': '#00ff00',
                        'target-arrow-color': '#00ff00'
                    }
                },
                {
                    selector: 'edge[edgeType="incoming"]',
                    style: {
                        'line-color': '#007bff',
                        'target-arrow-color': '#007bff'
                    }
                }
            ],
            layout: {
                name: 'concentric',
                concentric: function(node) {
                    return node.data('nodeType') === 'main' ? 10 : 0;
                },
                levelWidth: function() {
                    return 1;
                },
                padding: 50,
                animate: true
            }
        });

        // Track the expanded state of nodes
        const expandedNodes = new Set();

        // Add single-click event to toggle node size
        cy.on('click', 'node', function(evt) {
            const node = evt.target;

            if (expandedNodes.has(node.id())) {
                // Collapse the node
                node.animate({
                    style: {
                        'width': 120,
                        'height': 120,
                        'font-size': 14,
                        'text-max-width': 80
                    },
                    duration: 300
                });
                expandedNodes.delete(node.id());
            } else {
                // Expand the node
                node.animate({
                    style: {
                        'width': 250,
                        'height': 250,
                        'font-size': 30,
                        'text-max-width': 1000,
                        'text-wrap': 'wrap'
                    },
                    duration: 300
                });
                expandedNodes.add(node.id());
            }
        });

        // Add double-click event to redirect to the node's URL
        cy.on('dblclick', 'node', function(evt) {
            const node = evt.target;
            const url = node.data('id'); // Use the node's ID as the URL

            if (url) {
                window.open(url.startsWith('http') ? url : `https://${url}`, '_blank');
            }
        });
    </script>
</body>

</html>
