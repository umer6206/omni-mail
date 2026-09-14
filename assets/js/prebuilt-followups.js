/**
 * Campaign Follow-Up Library sequences for OmniMail behavioral flows.
 * Users pick ONE sequence (or None), or build a custom sequence in the UI.
 */
(function (window) {
  'use strict';

  function schedulesWeekdays() {
    return [
      { day: 1, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } },
      { day: 2, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } },
      { day: 3, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } },
      { day: 4, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } },
      { day: 5, timeWindow: { startHour: 9, startMinute: 0, endHour: 17, endMinute: 0 } },
    ];
  }

  var NO_ENGAGEMENT_RECOVERY = [
    {
      trigger: 'not_opened',
      actionType: 'email',
      delayDays: 0,
      followupEmailSubject: 'Still interested?',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #333;">Hey {{firstName}},</h2><p style="color: #555; line-height: 1.6;">We noticed you haven\'t been around lately, and we wanted to check in.</p><p style="color: #555; line-height: 1.6;">Is there anything we can help you with? We\'d love to hear from you.</p><div style="text-align: center; margin: 30px 0;"><a href="{{ctaLink}}" style="background-color: #00D2FF; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Yes, I\'m Still Interested</a></div></div>',
      sequenceType: 'No Engagement Recovery',
      stepDescription: 'Day 0 - Re-open attention loop',
      activeDaySchedules: schedulesWeekdays(),
    },
    {
      trigger: 'opened',
      actionType: 'email',
      delayDays: 2,
      followupEmailSubject: 'Quick follow-up + soft value',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #333;">Hi {{firstName}},</h2><p style="color: #555; line-height: 1.6;">Thanks for opening our last email! We wanted to share something that might interest you.</p><p style="color: #555; line-height: 1.6;">Over <strong>500+ customers</strong> have already benefited from our solution this month alone.</p><div style="text-align: center; margin: 30px 0;"><a href="{{ctaLink}}" style="background-color: #00D2FF; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">See How It Works</a></div></div>',
      sequenceType: 'No Engagement Recovery',
      stepDescription: 'Step 2A - Opened but no click',
      activeDaySchedules: schedulesWeekdays(),
    },
    {
      trigger: 'not_opened',
      actionType: 'email',
      delayDays: 2,
      followupEmailSubject: 'Different subject, different angle',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #333;">{{firstName}}, quick question...</h2><p style="color: #555; line-height: 1.6;">Our last message might have gotten buried in your inbox.</p><div style="text-align: center; margin: 30px 0;"><a href="{{ctaLink}}" style="background-color: #00D2FF; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Learn More</a></div></div>',
      sequenceType: 'No Engagement Recovery',
      stepDescription: 'Step 2B - Not opened, new angle',
      activeDaySchedules: schedulesWeekdays(),
    },
    {
      trigger: 'not_opened',
      actionType: 'both',
      delayDays: 4,
      followupEmailSubject: 'Last touch / break-up email',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #333;">{{firstName}}, this is our last email</h2><p style="color: #555; line-height: 1.6;">We don\'t want to fill your inbox with messages you\'re not interested in.</p><div style="text-align: center; margin: 30px 0;"><a href="{{ctaLink}}" style="background-color: #00D2FF; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Keep Me Updated</a></div></div>',
      followupTag: 'Cold - No Engagement Recovery Failed',
      sequenceType: 'No Engagement Recovery',
      stepDescription: 'Step 3 - Close the loop',
      activeDaySchedules: schedulesWeekdays(),
    },
  ];

  var ENGAGED_LEAD = [
    {
      trigger: 'clicked',
      actionType: 'email',
      delayDays: 1,
      followupEmailSubject: 'Saw you checking this out',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #333;">Hi {{firstName}},</h2><p style="color: #555; line-height: 1.6;">We noticed you were exploring what we have to offer – that\'s great!</p><div style="text-align: center; margin: 30px 0;"><a href="{{ctaLink}}" style="background-color: #00D2FF; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Continue Where You Left Off</a></div></div>',
      sequenceType: 'Engaged Lead',
      stepDescription: 'Step 1 - +24 Hours after click',
      activeDaySchedules: schedulesWeekdays(),
    },
    {
      trigger: 'clicked',
      actionType: 'email',
      delayDays: 3,
      followupEmailSubject: 'Common questions / objections answered',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #333;">{{firstName}}, got questions?</h2><p style="color: #555; line-height: 1.6;">Here are answers to the most common questions before getting started.</p><div style="text-align: center; margin: 30px 0;"><a href="{{ctaLink}}" style="background-color: #00D2FF; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Get Started Now</a></div></div>',
      sequenceType: 'Engaged Lead',
      stepDescription: 'Step 2 - FAQ / objection handling',
      activeDaySchedules: schedulesWeekdays(),
    },
    {
      trigger: 'clicked',
      actionType: 'both',
      delayDays: 3,
      followupEmailSubject: 'Nudge or incentive',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><h2 style="color: #333;">{{firstName}}, still thinking it over?</h2><p style="color: #555; line-height: 1.6;">A gentle reminder about what you were looking at.</p><div style="text-align: center; margin: 30px 0;"><a href="{{ctaLink}}" style="background-color: #00D2FF; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Take Action Now</a></div></div>',
      followupTag: 'Warm - Needs Nurture',
      sequenceType: 'Engaged Lead',
      stepDescription: 'Step 3 - Final nudge',
      activeDaySchedules: schedulesWeekdays(),
    },
  ];

  var NO_RESPONSE = [
    {
      trigger: 'not_opened',
      actionType: 'email',
      delayDays: 2,
      followupEmailSubject: 'Bump / resend with new subject',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><p style="color: #555; line-height: 1.6;">Hi {{firstName}},</p><p style="color: #555; line-height: 1.6;">Just wanted to bump this to the top of your inbox in case you missed it.</p><div style="text-align: center; margin: 30px 0;"><a href="{{ctaLink}}" style="background-color: #00D2FF; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Let\'s Connect</a></div></div>',
      sequenceType: 'No Response Recovery',
      stepDescription: 'Step 2 - +48 Hours bump',
      activeDaySchedules: schedulesWeekdays(),
    },
    {
      trigger: 'not_opened',
      actionType: 'email',
      delayDays: 3,
      followupEmailSubject: 'Different value angle',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><p style="color: #555; line-height: 1.6;">Hi {{firstName}},</p><p style="color: #555; line-height: 1.6;">I wanted to try a different angle – maybe my previous message didn\'t resonate.</p><div style="text-align: center; margin: 30px 0;"><a href="{{ctaLink}}" style="background-color: #00D2FF; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Learn How We Can Help</a></div></div>',
      sequenceType: 'No Response Recovery',
      stepDescription: 'Step 3 - New value angle',
      activeDaySchedules: schedulesWeekdays(),
    },
    {
      trigger: 'not_opened',
      actionType: 'both',
      delayDays: 7,
      followupEmailSubject: 'Close the loop',
      followupEmailContent: '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;"><p style="color: #555; line-height: 1.6;">Hi {{firstName}},</p><p style="color: #555; line-height: 1.6;">I\'ve reached out a few times and haven\'t heard back, so I wanted to close the loop.</p><p style="color: #888; font-size: 14px; margin-top: 30px;">P.S. If you\'d prefer not to receive emails from us, just let me know.</p></div>',
      followupTag: 'Cold - No Engagement',
      sequenceType: 'No Response Recovery',
      stepDescription: 'Step 4 - Close the loop',
      activeDaySchedules: schedulesWeekdays(),
    },
  ];

  window.OMNIMAIL_PREBUILT_SEQUENCES = {
    none: {
      id: 'none',
      name: 'None (Recommended for simple alerts)',
      description: 'Keep only the default behavioral email. No extra follow-up sequence.',
      recommendedFor: ['wishlistReminder', 'priceDrop', 'backInStock', 'postPurchase'],
      followups: [],
    },
    no_engagement_recovery: {
      id: 'no_engagement_recovery',
      name: 'No Engagement Recovery',
      description: 'For inactive or abandoned customers. Opens attention, soft value, then a break-up email.',
      recommendedFor: ['cartAbandonment', 'checkoutAbandonment', 'browseAbandonment', 'reEngagement'],
      followups: NO_ENGAGEMENT_RECOVERY,
    },
    engaged_lead: {
      id: 'engaged_lead',
      name: 'Engaged Lead',
      description: 'For people who clicked/interacted but did not convert. FAQ and nudge follow-ups.',
      recommendedFor: ['browseAbandonment'],
      followups: ENGAGED_LEAD,
    },
    no_response_recovery: {
      id: 'no_response_recovery',
      name: 'No Response Recovery',
      description: 'For cold/no-open recovery after the first email. Bump, new angle, then close the loop.',
      recommendedFor: [],
      followups: NO_RESPONSE,
    },
    custom: {
      id: 'custom',
      name: 'Custom Sequence',
      description: 'Build your own steps with triggers, delays, email content, and optional tags.',
      recommendedFor: [],
      followups: [],
      isCustom: true,
    },
  };

  // Backward-compatible alias (combined catalog, not applied as one blob anymore)
  window.OMNIMAIL_PREBUILT_FOLLOWUP_TEMPLATE = {
    id: 'campaign-followup-library',
    name: 'Campaign Follow-Up Library',
    description: 'Choose No Engagement Recovery, Engaged Lead, or No Response Recovery for this behavioral flow.',
    followups: NO_ENGAGEMENT_RECOVERY.concat(ENGAGED_LEAD, NO_RESPONSE),
  };
})(window);
