<?php

namespace Platform\Canvas\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Canvas\Models\CanvasType;

class CanvasTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = $this->getSystemTypes();

        foreach ($types as $data) {
            CanvasType::updateOrCreate(
                ['key' => $data['key'], 'team_id' => null],
                $data
            );
        }
    }

    private function getSystemTypes(): array
    {
        return [
            $this->bmcType(),
            $this->swotType(),
            $this->projectCanvasType(),
            $this->leanCanvasType(),
        ];
    }

    private function bmcType(): array
    {
        return [
            'key' => 'bmc',
            'name' => 'Business Model Canvas',
            'description' => 'The Business Model Canvas by Alexander Osterwalder describes how an organization creates, delivers, and captures value.',
            'purpose' => 'Use when designing or analyzing a business model. Ideal for startups, new ventures, or rethinking existing business models.',
            'methodology' => 'Alexander Osterwalder & Yves Pigneur',
            'icon' => 'heroicon-o-squares-2x2',
            'origin' => 'system',
            'is_active' => true,
            'entry_types' => ['text'],
            'block_definitions' => [
                [
                    'key' => 'customer_segments',
                    'label' => 'Customer Segments',
                    'description' => 'The different groups of people or organizations an enterprise aims to reach and serve.',
                    'position' => 1,
                    'guiding_questions' => [
                        'For whom are we creating value?',
                        'Who are our most important customers?',
                        'What are the customer archetypes?',
                        'Is this a mass market, niche market, segmented, diversified, or multi-sided platform?',
                    ],
                ],
                [
                    'key' => 'value_propositions',
                    'label' => 'Value Propositions',
                    'description' => 'The bundle of products and services that create value for a specific Customer Segment.',
                    'position' => 2,
                    'guiding_questions' => [
                        'What value do we deliver to the customer?',
                        'Which one of our customer problems are we helping to solve?',
                        'What bundles of products and services are we offering to each Customer Segment?',
                        'Which customer needs are we satisfying?',
                    ],
                ],
                [
                    'key' => 'channels',
                    'label' => 'Channels',
                    'description' => 'How a company communicates with and reaches its Customer Segments to deliver a Value Proposition.',
                    'position' => 3,
                    'guiding_questions' => [
                        'Through which channels do our Customer Segments want to be reached?',
                        'How are we reaching them now?',
                        'How are our channels integrated?',
                        'Which ones work best? Which ones are most cost-efficient?',
                    ],
                ],
                [
                    'key' => 'customer_relationships',
                    'label' => 'Customer Relationships',
                    'description' => 'The types of relationships a company establishes with specific Customer Segments.',
                    'position' => 4,
                    'guiding_questions' => [
                        'What type of relationship does each of our Customer Segments expect us to establish and maintain?',
                        'Which ones have we established?',
                        'How are they integrated with the rest of our business model?',
                        'How costly are they?',
                    ],
                ],
                [
                    'key' => 'revenue_streams',
                    'label' => 'Revenue Streams',
                    'description' => 'The cash a company generates from each Customer Segment.',
                    'position' => 5,
                    'guiding_questions' => [
                        'For what value are our customers really willing to pay?',
                        'For what do they currently pay?',
                        'How are they currently paying?',
                        'How would they prefer to pay?',
                        'How much does each Revenue Stream contribute to overall revenues?',
                    ],
                ],
                [
                    'key' => 'key_resources',
                    'label' => 'Key Resources',
                    'description' => 'The most important assets required to make a business model work.',
                    'position' => 6,
                    'guiding_questions' => [
                        'What Key Resources do our Value Propositions require?',
                        'What resources do our Distribution Channels require?',
                        'What resources do our Customer Relationships require?',
                        'What resources do our Revenue Streams require?',
                    ],
                ],
                [
                    'key' => 'key_activities',
                    'label' => 'Key Activities',
                    'description' => 'The most important things a company must do to make its business model work.',
                    'position' => 7,
                    'guiding_questions' => [
                        'What Key Activities do our Value Propositions require?',
                        'What activities do our Distribution Channels require?',
                        'What activities do our Customer Relationships require?',
                        'What activities do our Revenue Streams require?',
                    ],
                ],
                [
                    'key' => 'key_partners',
                    'label' => 'Key Partners',
                    'description' => 'The network of suppliers and partners that make the business model work.',
                    'position' => 8,
                    'guiding_questions' => [
                        'Who are our Key Partners?',
                        'Who are our key suppliers?',
                        'Which Key Resources are we acquiring from partners?',
                        'Which Key Activities do partners perform?',
                    ],
                ],
                [
                    'key' => 'cost_structure',
                    'label' => 'Cost Structure',
                    'description' => 'All costs incurred to operate a business model.',
                    'position' => 9,
                    'guiding_questions' => [
                        'What are the most important costs inherent in our business model?',
                        'Which Key Resources are most expensive?',
                        'Which Key Activities are most expensive?',
                        'Is the business more cost-driven or value-driven?',
                    ],
                ],
            ],
            'layout' => [
                'type' => 'grid',
                'columns' => 5,
                'rows' => 3,
                'areas' => 'kp ka vp cr cs / kp kr vp ch cs / cost cost cost rev rev',
                'area_map' => [
                    'key_partners' => 'kp',
                    'key_activities' => 'ka',
                    'value_propositions' => 'vp',
                    'customer_relationships' => 'cr',
                    'customer_segments' => 'cs',
                    'key_resources' => 'kr',
                    'channels' => 'ch',
                    'cost_structure' => 'cost',
                    'revenue_streams' => 'rev',
                ],
            ],
            'analysis_config' => [
                'strategy' => 'completeness',
                'thresholds' => ['good' => 80, 'partial' => 50, 'minimal' => 1],
            ],
        ];
    }

    private function swotType(): array
    {
        return [
            'key' => 'swot',
            'name' => 'SWOT Analysis',
            'description' => 'A strategic planning framework to identify Strengths, Weaknesses, Opportunities, and Threats.',
            'purpose' => 'Use for strategic analysis of an organization, product, or project. Combine with BMC for deeper business model insights.',
            'methodology' => 'Albert Humphrey / Stanford Research Institute',
            'icon' => 'heroicon-o-arrow-path-rounded-square',
            'origin' => 'system',
            'is_active' => true,
            'entry_types' => ['text'],
            'block_definitions' => [
                [
                    'key' => 'strengths',
                    'label' => 'Strengths',
                    'description' => 'Internal attributes and resources that support a successful outcome.',
                    'position' => 1,
                    'guiding_questions' => [
                        'What do we do well?',
                        'What unique resources do we have?',
                        'What do others see as our strengths?',
                        'What competitive advantages do we have?',
                    ],
                ],
                [
                    'key' => 'weaknesses',
                    'label' => 'Weaknesses',
                    'description' => 'Internal attributes and resources that work against a successful outcome.',
                    'position' => 2,
                    'guiding_questions' => [
                        'What could we improve?',
                        'Where do we have fewer resources than others?',
                        'What are others likely to see as weaknesses?',
                        'What factors lose us sales or customers?',
                    ],
                ],
                [
                    'key' => 'opportunities',
                    'label' => 'Opportunities',
                    'description' => 'External factors the organization could exploit to its advantage.',
                    'position' => 3,
                    'guiding_questions' => [
                        'What opportunities are open to us?',
                        'What trends could we take advantage of?',
                        'How can we turn our strengths into opportunities?',
                        'What changes in the market could benefit us?',
                    ],
                ],
                [
                    'key' => 'threats',
                    'label' => 'Threats',
                    'description' => 'External factors that could jeopardize the organization\'s success.',
                    'position' => 4,
                    'guiding_questions' => [
                        'What threats could harm us?',
                        'What is our competition doing?',
                        'What regulations or policies could affect us?',
                        'Could any of our weaknesses seriously threaten our business?',
                    ],
                ],
            ],
            'layout' => [
                'type' => 'grid',
                'columns' => 2,
                'rows' => 2,
            ],
            'analysis_config' => [
                'strategy' => 'completeness',
                'thresholds' => ['good' => 80, 'partial' => 50, 'minimal' => 1],
            ],
        ];
    }

    private function projectCanvasType(): array
    {
        return [
            'key' => 'project-canvas',
            'name' => 'Project Canvas',
            'description' => 'A one-page project overview capturing all essential project dimensions.',
            'purpose' => 'Use when starting a new project or reviewing an existing one. Provides a holistic view of project planning.',
            'methodology' => 'Jim Kalbach / Project Canvas Method',
            'icon' => 'heroicon-o-clipboard-document-list',
            'origin' => 'system',
            'is_active' => true,
            'entry_types' => ['text', 'date', 'person', 'amount'],
            'block_definitions' => [
                [
                    'key' => 'project_goal',
                    'label' => 'Project Goal',
                    'description' => 'The overarching objective and purpose of the project.',
                    'position' => 1,
                    'guiding_questions' => [
                        'What is the main goal of this project?',
                        'What problem does this project solve?',
                        'How does this project align with organizational strategy?',
                        'What does success look like?',
                    ],
                ],
                [
                    'key' => 'scope',
                    'label' => 'Scope',
                    'description' => 'What is included in and excluded from the project.',
                    'position' => 2,
                    'guiding_questions' => [
                        'What deliverables are included?',
                        'What is explicitly out of scope?',
                        'What are the key constraints (time, budget, quality)?',
                        'What assumptions are we making?',
                    ],
                ],
                [
                    'key' => 'stakeholders',
                    'label' => 'Stakeholders',
                    'description' => 'Key people and groups affected by or influencing the project.',
                    'position' => 3,
                    'guiding_questions' => [
                        'Who are the project sponsors?',
                        'Who are the end users / beneficiaries?',
                        'Who needs to be consulted or informed?',
                        'Who has decision-making authority?',
                    ],
                ],
                [
                    'key' => 'risks',
                    'label' => 'Risks',
                    'description' => 'Potential threats and uncertainties that could impact the project.',
                    'position' => 4,
                    'guiding_questions' => [
                        'What could go wrong?',
                        'What external factors could impact the project?',
                        'What dependencies exist?',
                        'What is the impact and likelihood of each risk?',
                    ],
                ],
                [
                    'key' => 'milestones',
                    'label' => 'Milestones',
                    'description' => 'Key dates and deliverables marking project progress.',
                    'position' => 5,
                    'guiding_questions' => [
                        'What are the key deadlines?',
                        'What deliverables mark each phase?',
                        'What are the decision gates?',
                        'When is the final delivery date?',
                    ],
                ],
                [
                    'key' => 'resources',
                    'label' => 'Resources',
                    'description' => 'People, tools, and capabilities needed for the project.',
                    'position' => 6,
                    'guiding_questions' => [
                        'What team members are needed?',
                        'What skills and expertise are required?',
                        'What tools and infrastructure are needed?',
                        'Are there external resources or vendors involved?',
                    ],
                ],
                [
                    'key' => 'budget',
                    'label' => 'Budget',
                    'description' => 'Financial planning and cost tracking for the project.',
                    'position' => 7,
                    'guiding_questions' => [
                        'What is the total budget?',
                        'How is the budget allocated across phases?',
                        'What are the major cost drivers?',
                        'Is there a contingency reserve?',
                    ],
                ],
                [
                    'key' => 'communication',
                    'label' => 'Communication',
                    'description' => 'How project information is shared with stakeholders.',
                    'position' => 8,
                    'guiding_questions' => [
                        'How often are status updates provided?',
                        'What communication channels are used?',
                        'Who receives which information?',
                        'How are decisions documented and communicated?',
                    ],
                ],
                [
                    'key' => 'governance',
                    'label' => 'Governance',
                    'description' => 'Decision-making structures and escalation paths.',
                    'position' => 9,
                    'guiding_questions' => [
                        'Who makes which decisions?',
                        'What is the escalation path?',
                        'How are changes to scope managed?',
                        'What approval processes exist?',
                    ],
                ],
            ],
            'layout' => [
                'type' => 'grid',
                'columns' => 3,
                'rows' => 3,
            ],
            'analysis_config' => [
                'strategy' => 'traffic_light',
                'critical_blocks' => ['project_goal', 'scope', 'milestones', 'risks'],
                'risk_block' => 'risks',
                'milestone_block' => 'milestones',
                'thresholds' => ['green' => 70, 'yellow' => 40],
                'weights' => [
                    'completeness' => 40,
                    'critical_blocks' => 30,
                    'risk_assessment' => 15,
                    'milestone_health' => 15,
                ],
            ],
        ];
    }

    private function leanCanvasType(): array
    {
        return [
            'key' => 'lean-canvas',
            'name' => 'Lean Canvas',
            'description' => 'An adaptation of the Business Model Canvas focused on problems, solutions, and key metrics for lean startups.',
            'purpose' => 'Use when validating startup ideas or pivoting. Focuses on problem-solution fit and key metrics.',
            'methodology' => 'Ash Maurya',
            'icon' => 'heroicon-o-rocket-launch',
            'origin' => 'system',
            'is_active' => true,
            'entry_types' => ['text'],
            'block_definitions' => [
                [
                    'key' => 'problem',
                    'label' => 'Problem',
                    'description' => 'Top 1-3 problems your customers face.',
                    'position' => 1,
                    'guiding_questions' => [
                        'What are the top 3 problems?',
                        'How are these problems solved today?',
                        'What are existing alternatives?',
                    ],
                ],
                [
                    'key' => 'solution',
                    'label' => 'Solution',
                    'description' => 'Your proposed solution for each problem.',
                    'position' => 2,
                    'guiding_questions' => [
                        'What is the simplest solution for each problem?',
                        'What is your minimum viable product (MVP)?',
                        'How does your solution differ from alternatives?',
                    ],
                ],
                [
                    'key' => 'key_metrics',
                    'label' => 'Key Metrics',
                    'description' => 'The key numbers that tell you how your business is doing.',
                    'position' => 3,
                    'guiding_questions' => [
                        'What key activities do you measure?',
                        'What are your pirate metrics (AARRR)?',
                        'What does success look like quantitatively?',
                    ],
                ],
                [
                    'key' => 'unique_value_proposition',
                    'label' => 'Unique Value Proposition',
                    'description' => 'A single, clear, compelling message that states why you are different and worth buying.',
                    'position' => 4,
                    'guiding_questions' => [
                        'What is your clear, compelling message?',
                        'What makes you different and worth buying?',
                        'What is your high-level concept (X for Y)?',
                    ],
                ],
                [
                    'key' => 'unfair_advantage',
                    'label' => 'Unfair Advantage',
                    'description' => 'Something that cannot easily be bought or copied.',
                    'position' => 5,
                    'guiding_questions' => [
                        'What competitive advantage do you have that cannot be easily copied?',
                        'Do you have insider information, expert endorsements, or a dream team?',
                        'What network effects can you leverage?',
                    ],
                ],
                [
                    'key' => 'channels',
                    'label' => 'Channels',
                    'description' => 'Free and paid paths to your customers.',
                    'position' => 6,
                    'guiding_questions' => [
                        'What are your inbound channels (blogs, SEO, social media)?',
                        'What are your outbound channels (SEM, advertising, trade shows)?',
                        'What are your free vs. paid channel strategies?',
                    ],
                ],
                [
                    'key' => 'customer_segments',
                    'label' => 'Customer Segments',
                    'description' => 'Your target customers and users.',
                    'position' => 7,
                    'guiding_questions' => [
                        'Who are your target customers?',
                        'Who are the early adopters?',
                        'What are the characteristics of your ideal customer?',
                    ],
                ],
                [
                    'key' => 'cost_structure',
                    'label' => 'Cost Structure',
                    'description' => 'Customer acquisition costs, distribution costs, hosting, people, etc.',
                    'position' => 8,
                    'guiding_questions' => [
                        'What are your fixed costs?',
                        'What are your variable costs?',
                        'What is your customer acquisition cost (CAC)?',
                        'What is your burn rate?',
                    ],
                ],
                [
                    'key' => 'revenue_streams',
                    'label' => 'Revenue Streams',
                    'description' => 'Your revenue model and lifetime value.',
                    'position' => 9,
                    'guiding_questions' => [
                        'What is your revenue model?',
                        'What is the lifetime value (LTV) of a customer?',
                        'What is your pricing strategy?',
                        'What gross margins do you target?',
                    ],
                ],
            ],
            'layout' => [
                'type' => 'grid',
                'columns' => 5,
                'rows' => 3,
                'areas' => 'prob sol km uvp ua / prob ch km cs ua / cost cost cost rev rev',
                'area_map' => [
                    'problem' => 'prob',
                    'solution' => 'sol',
                    'key_metrics' => 'km',
                    'unique_value_proposition' => 'uvp',
                    'unfair_advantage' => 'ua',
                    'channels' => 'ch',
                    'customer_segments' => 'cs',
                    'cost_structure' => 'cost',
                    'revenue_streams' => 'rev',
                ],
            ],
            'analysis_config' => [
                'strategy' => 'completeness',
                'thresholds' => ['good' => 80, 'partial' => 50, 'minimal' => 1],
            ],
        ];
    }
}
